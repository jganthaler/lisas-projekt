<?php

namespace App\Policies;

use App\Models\InventoryItem;
use App\Models\User;

/**
 * Items follow the access rules of their drawer's freezer.
 */
class InventoryItemPolicy
{
    public function view(User $user, InventoryItem $item): bool
    {
        return $user->isMemberOf($item->drawer->freezer);
    }

    public function update(User $user, InventoryItem $item): bool
    {
        return $user->isMemberOf($item->drawer->freezer);
    }

    public function delete(User $user, InventoryItem $item): bool
    {
        return $user->isMemberOf($item->drawer->freezer);
    }
}
