<?php

namespace App\Support;

use App\Models\Drawer;
use App\Models\Freezer;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * The inventory as data for the offline page.
 *
 * The page itself is a static file (public/offline.html) and cannot render
 * anything from the server. These two directions are all it needs:
 *
 * - snapshot() hands the complete inventory of a user over as JSON. The page
 *   keeps it in the local storage of the browser and draws it while there is
 *   no network.
 * - apply() takes the changes that were made without a network and writes them
 *   into the database. They travel as a list of operations, each one about a
 *   single item and addressed by its UUID - the identity the page can know.
 *
 * The words of the interface travel along with the snapshot (the categories,
 * the quick choices of the best before date, the state of a best before date
 * and its relative wording). They come from the translation files, so the
 * offline page shows what the app shows without carrying a second copy of it.
 *
 * A conflict is resolved by the last change: an operation carries the values of
 * the item as the page knows them, so the sync that arrives last wins. An
 * operation that is not allowed - a drawer of another household, an item that
 * was deleted in the meantime - is counted as skipped instead of taking the
 * rest of the batch down with it.
 */
class OfflineInventory
{
    /** Ceiling for one batch, so a single request cannot grow without end. */
    public const MAX_OPERATIONS = 5000;

    /**
     * The complete inventory of the user, ready for the offline page.
     *
     * @return array<string, mixed>
     */
    public static function snapshot(User $user): array
    {
        // Makes sure a brand new account owns a freezer before it is listed.
        FreezerContext::current($user);

        $freezers = $user->freezers()
            ->with([
                'drawers' => fn (HasMany $query) => $query->orderBy('position')->orderBy('id'),
                'drawers.items',
            ])
            ->orderBy('name')
            ->get();

        return [
            'generated_at' => Carbon::now()->toIso8601String(),
            'locale' => app()->getLocale(),
            'warning_days' => InventoryItem::WARNING_DAYS,
            'labels' => self::labels(),
            'freezers' => $freezers->map(fn (Freezer $freezer): array => [
                'id' => $freezer->getKey(),
                'name' => $freezer->name,
                'drawers' => $freezer->drawers->map(fn (Drawer $drawer): array => [
                    'id' => $drawer->getKey(),
                    'name' => $drawer->name,
                    'items' => $drawer->items->map(fn (InventoryItem $item): array => [
                        'uuid' => $item->uuid,
                        'name' => $item->name,
                        'quantity' => (int) $item->quantity,
                        'best_before' => $item->best_before?->toDateString(),
                        'category' => $item->category,
                    ])->all(),
                ])->all(),
            ])->all(),
        ];
    }

    /**
     * Write the changes of the offline page into the inventory.
     *
     * @param  array<int, mixed>  $operations
     * @return array{applied: int, skipped: int}
     */
    public static function apply(User $user, array $operations): array
    {
        $applied = 0;
        $skipped = 0;

        foreach ($operations as $operation) {
            $done = is_array($operation) && match ($operation['type'] ?? null) {
                'save' => self::save($user, $operation),
                'delete' => self::delete($user, $operation),
                default => false,
            };

            $done ? $applied++ : $skipped++;
        }

        return ['applied' => $applied, 'skipped' => $skipped];
    }

    /**
     * One item as the offline page knows it: an item that does not exist yet is
     * created, an existing one is brought to exactly these values - that is how
     * a moved item changes its drawer as well. The UUID is what makes this
     * repeatable: the same operation twice leaves one item.
     *
     * @param  array<string, mixed>  $operation
     */
    private static function save(User $user, array $operation): bool
    {
        $validated = self::validate($operation, [
            'uuid' => ['required', 'uuid'],
            'drawer_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:80'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'best_before' => ['nullable', 'date'],
            'category' => ['nullable', Rule::in(InventoryItem::categoryKeys())],
        ]);

        if ($validated === null) {
            return false;
        }

        $drawer = Drawer::query()->find($validated['drawer_id']);

        // The place has to be one the user may work in - a foreign or an
        // invented drawer is not, so the operation stays unwritten.
        if ($drawer === null || ! Gate::forUser($user)->allows('update', $drawer)) {
            return false;
        }

        $item = self::find($validated['uuid']);

        // An item that exists already may only be changed by somebody who may
        // touch it; the UUID of another household stays untouched.
        if ($item !== null && ! Gate::forUser($user)->allows('update', $item)) {
            return false;
        }

        $attributes = [
            'name' => $validated['name'],
            'quantity' => (int) $validated['quantity'],
            'best_before' => $validated['best_before'] ?? null,
            'category' => $validated['category'] ?? null,
        ];

        if ($item === null) {
            $item = $drawer->items()->make($attributes);
            $item->uuid = $validated['uuid'];
        } else {
            $item->fill($attributes);
            $item->drawer()->associate($drawer);
        }

        $item->save();

        return true;
    }

    /**
     * Remove the item the page deleted. An item that is gone already counts as
     * done: the second sync of the same change has nothing left to do.
     *
     * @param  array<string, mixed>  $operation
     */
    private static function delete(User $user, array $operation): bool
    {
        $validated = self::validate($operation, [
            'uuid' => ['required', 'uuid'],
        ]);

        if ($validated === null) {
            return false;
        }

        $item = self::find($validated['uuid']);

        if ($item === null) {
            return true;
        }

        if (! Gate::forUser($user)->allows('delete', $item)) {
            return false;
        }

        $item->delete();

        return true;
    }

    /**
     * The words the offline page puts next to the data. They are the ones of
     * the translation files, so a language the app speaks is a language the
     * offline page speaks as well.
     *
     * @return array<string, mixed>
     */
    private static function labels(): array
    {
        $presets = [];

        foreach (InventoryItem::BEST_BEFORE_PRESETS as $key => $months) {
            $presets[] = [
                'key' => $key,
                'label' => __('drawers.quick_best_before.'.$key),
                'months' => $months,
            ];
        }

        return [
            'state' => [
                InventoryItem::STATE_EXPIRED => __('items.state.expired'),
                InventoryItem::STATE_SOON => __('items.state.soon'),
                InventoryItem::STATE_FRESH => __('items.state.fresh'),
                InventoryItem::STATE_UNKNOWN => __('items.state.unknown'),
            ],
            'mhd' => [
                'none' => __('items.mhd.none'),
                'today' => __('items.mhd.today'),
                'tomorrow' => __('items.mhd.tomorrow'),
                'yesterday' => __('items.mhd.yesterday'),
                'days_ago' => __('items.mhd.days_ago'),
                'in_days' => __('items.mhd.in_days'),
            ],
            'categories' => InventoryItem::categoryLabels(),
            'no_category' => __('drawers.no_category'),
            'presets' => $presets,
        ];
    }

    private static function find(string $uuid): ?InventoryItem
    {
        return InventoryItem::query()->where('uuid', $uuid)->first();
    }

    /**
     * The values of one operation, or null while they do not hold up. A single
     * operation that does not pass is skipped instead of taking the rest of the
     * batch with it - the offline page would otherwise never get rid of a queue
     * that carries one impossible change.
     *
     * @param  array<string, mixed>  $operation
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>|null
     */
    private static function validate(array $operation, array $rules): ?array
    {
        $validator = Validator::make($operation, $rules);

        return $validator->fails() ? null : $validator->validated();
    }
}
