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
     * Remove one item. The question comes first and belongs to the row: its
     * trash button opens the dialog of the app (see
     * resources/views/components/confirm-dialog.blade.php), and only the answer
     * inside that dialog calls this method.
     */
    public function deleteItem(int $itemId): void
    {
        $item = InventoryItem::query()->findOrFail($itemId);

        Gate::authorize('delete', $item);

        $item->delete();

        $this->itemFeedback = __('items.deleted');
    }
}
