<?php

namespace App\Models;

use App\Policies\FreezerPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * A freezer is the shared inventory itself: it owns the drawers and - through
 * them - all inventory items. Exactly one user is the owner, further users can
 * be attached as members through an invitation.
 */
#[Fillable(['name'])]
#[UsePolicy(FreezerPolicy::class)]
class Freezer extends Model
{
    use HasFactory;

    public const DEFAULT_NAME = 'Mein Gefrierschrank';

    public const DEFAULT_DRAWER_COUNT = 3;

    /**
     * Longest name the app accepts for a freezer - the forms validate against
     * it and the model shortens a name that is too long.
     */
    public const NAME_MAX_LENGTH = 60;

    /**
     * Create the very first freezer of a user: one freezer, three drawers and
     * the owner membership. Called lazily on the first access of the app.
     */
    public static function provisionFor(User $owner): self
    {
        return self::createFor($owner, self::DEFAULT_NAME);
    }

    /**
     * Create another freezer for its owner. Every freezer starts with the
     * default drawers and the owner membership - without them nobody could use
     * it, because the owner is the only member from the beginning.
     */
    public static function createFor(User $owner, string $name): self
    {
        return DB::transaction(function () use ($owner, $name): self {
            $freezer = $owner->ownedFreezers()->create(['name' => $name]);

            $freezer->memberships()->create([
                'user_id' => $owner->getKey(),
                'role' => FreezerMembership::ROLE_OWNER,
            ]);

            for ($position = 1; $position <= self::DEFAULT_DRAWER_COUNT; $position++) {
                $freezer->drawers()->create([
                    'name' => "Schublade {$position}",
                    'position' => $position,
                ]);
            }

            return $freezer;
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return HasMany<FreezerMembership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(FreezerMembership::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'freezer_memberships')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Drawers in the user defined order.
     *
     * @return HasMany<Drawer, $this>
     */
    public function drawers(): HasMany
    {
        return $this->hasMany(Drawer::class)->orderBy('position')->orderBy('id');
    }

    /**
     * All items of this freezer, independent of their drawer.
     *
     * @return HasManyThrough<InventoryItem, Drawer, $this>
     */
    public function items(): HasManyThrough
    {
        return $this->hasManyThrough(InventoryItem::class, Drawer::class);
    }

    /**
     * @return HasMany<Invitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->owner_id === $user->getKey();
    }

    public function hasMember(User $user): bool
    {
        return $this->memberships()
            ->where('user_id', $user->getKey())
            ->exists();
    }

    public function itemCount(): int
    {
        return $this->items()->count();
    }

    /**
     * Items whose best before date is today or within the warning window.
     */
    public function expiringItemCount(): int
    {
        return $this->items()->expiringSoon()->count();
    }

    public function expiredItemCount(): int
    {
        return $this->items()->expired()->count();
    }

    /**
     * Number shown on the "!" warning button of the freezer.
     */
    public function warningItemCount(): int
    {
        return $this->expiredItemCount() + $this->expiringItemCount();
    }

    /**
     * Item and warning counters of every drawer, resolved with a single query:
     *
     *     [drawer_id => ['items' => 3, 'warnings' => 1]]
     *
     * @return array<int, array{items: int, warnings: int}>
     */
    public function drawerSummary(): array
    {
        $drawerIds = $this->drawers()->pluck('id');

        if ($drawerIds->isEmpty()) {
            return [];
        }

        $threshold = Carbon::today()->addDays(InventoryItem::WARNING_DAYS)->toDateString();

        return InventoryItem::query()
            ->whereIn('drawer_id', $drawerIds)
            ->groupBy('drawer_id')
            ->selectRaw('drawer_id, count(*) as item_count')
            ->selectRaw(
                'sum(case when best_before is not null and best_before <= ? then 1 else 0 end) as warning_count',
                [$threshold]
            )
            ->get()
            ->mapWithKeys(fn ($row): array => [
                (int) $row->drawer_id => [
                    'items' => (int) $row->item_count,
                    'warnings' => (int) $row->warning_count,
                ],
            ])
            ->all();
    }
}
