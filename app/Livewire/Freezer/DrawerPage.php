<?php

namespace App\Livewire\Freezer;

use App\Livewire\Concerns\FollowsOfflineChanges;
use App\Livewire\Concerns\InteractsWithItems;
use App\Models\Drawer;
use App\Models\Freezer;
use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Everything inside a single drawer: adding, editing, refilling, moving and
 * removing items. The drawer itself is bound through the route, so the id
 * cannot be manipulated from the browser.
 *
 * An item that lies in the wrong place is not deleted and entered again but
 * moved: into another drawer or into a drawer of another freezer of the
 * household (see moveTargets()).
 *
 * A category is optional: whoever wants to can sort an item into one of the
 * categories of the inventory filter (see InventoryItem::categoryLabels()),
 * but nothing forces it.
 */
#[Layout('layouts.app')]
#[Title('drawers.title')]
class DrawerPage extends Component
{
    use FollowsOfflineChanges;
    use InteractsWithItems;

    #[Locked]
    public int $drawerId;

    public string $name = '';

    public string $quantity = '1';

    public string $bestBefore = '';

    /** Empty means "without a category". */
    public string $category = '';

    public ?int $editingItemId = null;

    public string $editName = '';

    public string $editQuantity = '1';

    public string $editBestBefore = '';

    /** Empty means "without a category". */
    public string $editCategory = '';

    /** The item whose "move" form stands open, null while none is. */
    public ?int $movingItemId = null;

    /** The drawer the item is about to be moved into - empty until it is chosen. */
    public string $moveTargetDrawerId = '';

    public function mount(Drawer $drawer): void
    {
        Gate::authorize('view', $drawer);

        $this->drawerId = $drawer->getKey();

        // A list outside the drawer (the inventory, the MHD watch list) links
        // straight to one of its items: the address carries "?item=<id>" and
        // the form of that item stands open as soon as the drawer appears.
        // Looking the item up in the list first would be the long way round
        // (see resources/views/components/item-row.blade.php).
        $this->openItemFromQuery($drawer);
    }

    public function addItem(): void
    {
        $drawer = $this->drawer();

        Gate::authorize('update', $drawer);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:80'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'bestBefore' => ['nullable', 'date'],
            'category' => ['nullable', Rule::in(InventoryItem::categoryKeys())],
        ]);

        $drawer->items()->create([
            'name' => $validated['name'],
            'quantity' => (int) $validated['quantity'],
            'best_before' => $validated['bestBefore'] ?: null,
            'category' => $validated['category'] ?: null,
        ]);

        // The name and the date belong to the item that was just entered, the
        // category usually does not: whoever fills a drawer with vegetables
        // sorts them all the same way, so the choice stays for the next item.
        // It stays visible in the field, nothing happens behind the back of the
        // user, and one tap brings back "Ohne Kategorie".
        $this->reset('name', 'bestBefore');
        $this->quantity = '1';
        $this->resetErrorBag();
        $this->itemFeedback = __('drawers.item_added');

        // The cursor stays where the last item was typed, so a whole drawer can
        // be filled without ever reaching for the field again (the form listens
        // for this event, see the view).
        $this->dispatch('item-added');
    }

    /**
     * Quick input of the best before date: "3 Monate", "6 Monate", "1 Jahr" and
     * "2 Jahre" (see InventoryItem::BEST_BEFORE_PRESETS).
     *
     * A tap only fills the field of the form - saved is what that field holds,
     * so the date can still be corrected by hand before "Hinzufügen". An
     * unknown choice changes nothing at all.
     *
     * Both forms offer the same four choices - the one for a new item and the
     * one that corrects an item that is already there - therefore the second
     * argument names the field to fill. It is a whitelist: a request that names
     * any other field changes nothing.
     */
    public function useBestBeforePreset(string $preset, string $field = 'bestBefore'): void
    {
        $date = InventoryItem::bestBeforeForPreset($preset);

        if ($date === null || ! in_array($field, ['bestBefore', 'editBestBefore'], true)) {
            return;
        }

        $this->{$field} = $date;
        $this->resetErrorBag($field);
    }

    public function startEditingItem(int $itemId): void
    {
        $item = $this->findItem($itemId);

        Gate::authorize('update', $item);

        // A row shows either the edit form or the move form, never both.
        $this->cancelMovingItem();

        $this->editingItemId = $item->getKey();
        $this->editName = $item->name;
        $this->editQuantity = (string) $item->quantity;
        $this->editBestBefore = $item->best_before?->format('Y-m-d') ?? '';
        $this->editCategory = $item->category ?? '';
        $this->resetErrorBag();
    }

    public function saveItem(): void
    {
        $item = $this->findItem((int) $this->editingItemId);

        Gate::authorize('update', $item);

        $validated = $this->validate([
            'editName' => ['required', 'string', 'max:80'],
            'editQuantity' => ['required', 'integer', 'min:1', 'max:999'],
            'editBestBefore' => ['nullable', 'date'],
            'editCategory' => ['nullable', Rule::in(InventoryItem::categoryKeys())],
        ]);

        $item->update([
            'name' => $validated['editName'],
            'quantity' => (int) $validated['editQuantity'],
            'best_before' => $validated['editBestBefore'] ?: null,
            'category' => $validated['editCategory'] ?: null,
        ]);

        $this->cancelEditingItem();
        $this->itemFeedback = __('drawers.item_updated');
    }

    public function cancelEditingItem(): void
    {
        $this->reset('editingItemId', 'editName', 'editQuantity', 'editBestBefore', 'editCategory');
        $this->resetErrorBag();
    }

    /**
     * Open the "move" form of an item. Its target may be any drawer of the
     * household (see moveTargets()) - another drawer of this freezer or one of
     * another freezer. Only one form stands open per row, so an open edit form
     * is closed first.
     */
    public function startMovingItem(int $itemId): void
    {
        $item = $this->findItem($itemId);

        Gate::authorize('update', $item);

        $this->cancelEditingItem();

        $this->movingItemId = $item->getKey();
        $this->moveTargetDrawerId = '';
        $this->resetErrorBag();
    }

    /**
     * Put the item into the chosen drawer. It keeps its name, its quantity, its
     * best before date and its category - only its place changes, so a product
     * that was put away in a hurry does not have to be entered again.
     */
    public function moveItem(): void
    {
        $item = $this->findItem((int) $this->movingItemId);

        Gate::authorize('update', $item);

        $validated = $this->validate([
            'moveTargetDrawerId' => ['required', 'integer'],
        ]);

        $target = $this->moveTargets()
            ->flatMap(fn (Freezer $freezer) => $freezer->drawers)
            ->first(fn (Drawer $drawer): bool => $drawer->getKey() === (int) $validated['moveTargetDrawerId']);

        // Only an offered drawer can be the target. A foreign one, an invented
        // one and the one the item already lies in are all unknown here.
        if ($target === null) {
            $this->addError('moveTargetDrawerId', __('drawers.move_invalid'));

            return;
        }

        // The place of an item is the foreign key to its drawer. It is not part
        // of the fillable list, therefore it is set through the relation.
        $item->drawer()->associate($target);
        $item->save();

        $this->cancelMovingItem();
        $this->itemFeedback = __('drawers.item_moved', [
            'name' => $item->name,
            'drawer' => $target->name,
            'freezer' => $target->freezer->name,
        ]);
    }

    public function cancelMovingItem(): void
    {
        $this->reset('movingItemId', 'moveTargetDrawerId');
        $this->resetErrorBag();
    }

    public function render()
    {
        $drawer = $this->drawer();

        return view('livewire.freezer.drawer-page', [
            'drawer' => $drawer,
            'freezer' => $drawer->freezer,
            'items' => $drawer->items()->get(),
            'categories' => InventoryItem::categoryLabels(),
            'bestBeforePresets' => InventoryItem::bestBeforePresetLabels(),
            // The list of targets is only needed while the form stands open -
            // the button itself is offered for every item (see the view).
            'moveTargets' => $this->movingItemId === null ? collect() : $this->moveTargets(),
        ]);
    }

    /**
     * Every drawer an item of this page could move into: the drawers of every
     * freezer the user may access - the own ones and the shared ones - with the
     * current drawer left out. Moving an item into the drawer it already lies in
     * would change nothing, so it is not offered.
     *
     * @return Collection<int, Freezer>
     */
    private function moveTargets(): Collection
    {
        return auth()->user()->freezers()
            ->with(['drawers' => fn (HasMany $query) => $query->whereKeyNot($this->drawerId)])
            ->orderBy('name')
            ->get()
            ->filter(fn (Freezer $freezer): bool => $freezer->drawers->isNotEmpty())
            ->values();
    }

    private function drawer(): Drawer
    {
        return Drawer::query()->findOrFail($this->drawerId);
    }

    /**
     * Open the form of the item the address points at - as long as it names an
     * item of this drawer. An unknown or foreign id changes nothing at all: the
     * drawer then simply appears the way it always does.
     */
    private function openItemFromQuery(Drawer $drawer): void
    {
        $itemId = (int) request()->query('item');

        if ($itemId <= 0) {
            return;
        }

        $item = $drawer->items()->whereKey($itemId)->first();

        if ($item === null) {
            return;
        }

        $this->startEditingItem($item->getKey());
    }

    private function findItem(int $itemId): InventoryItem
    {
        return $this->drawer()->items()->whereKey($itemId)->firstOrFail();
    }
}
