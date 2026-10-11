<?php

namespace App\Livewire\Concerns;

use App\Models\InventoryItem;
use Illuminate\Support\Facades\Gate;

/**
 * Quantity and delete handling shared by every screen that lists items.
 *
 * Authorization always runs through the item policy, so a member can never
 * touch items of a freezer they do not belong to - no matter which component
 * triggered the action.
 */
trait InteractsWithItems
{
    public ?string $itemFeedback = null;

    /**
     * The item whose delete question is currently open. The question is asked
     * inside the row (see resources/views/components/item-row.blade.php) instead
     * of using the browser dialog, so the id has to survive the round trip.
     */
    public ?int $confirmingItemId = null;

    public function incrementQuantity(int $itemId): void
    {
        $item = InventoryItem::query()->findOrFail($itemId);

        Gate::authorize('update', $item);

        $item->increment('quantity');
    }

    public function decrementQuantity(int $itemId): void
    {
        $item = InventoryItem::query()->findOrFail($itemId);

        Gate::authorize('update', $item);

        // Never drop below one piece - removing an item is an explicit action.
        if ($item->quantity <= 1) {
            return;
        }

        $item->decrement('quantity');
    }

    /**
     * Show the delete question for one item. It only asks - nothing is removed
     * before deleteItem() is called with the same id.
     */
    public function confirmDelete(int $itemId): void
    {
        $item = InventoryItem::query()->findOrFail($itemId);

        Gate::authorize('delete', $item);

        $this->confirmingItemId = $item->getKey();
        $this->itemFeedback = null;
    }

    public function cancelDelete(): void
    {
        $this->confirmingItemId = null;
    }

    public function deleteItem(int $itemId): void
    {
        $item = InventoryItem::query()->findOrFail($itemId);

        Gate::authorize('delete', $item);

        $item->delete();

        $this->confirmingItemId = null;
        $this->itemFeedback = 'Artikel gelöscht.';
    }
}
