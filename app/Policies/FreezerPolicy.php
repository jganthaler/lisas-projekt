<?php

namespace App\Policies;

use App\Models\Freezer;
use App\Models\User;

/**
 * Every member of a freezer may look at it and work with its content, but only
 * the owner may rename it, invite new people or remove members.
 */
class FreezerPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Freezer $freezer): bool
    {
        return $user->isMemberOf($freezer);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Freezer $freezer): bool
    {
        return $freezer->isOwnedBy($user);
    }

    public function delete(User $user, Freezer $freezer): bool
    {
        return $freezer->isOwnedBy($user);
    }

    /**
     * Handing out invitation links is reserved for the owner.
     */
    public function invite(User $user, Freezer $freezer): bool
    {
        return $freezer->isOwnedBy($user);
    }

    /**
     * Removing another member is reserved for the owner as well.
     */
    public function manageMembers(User $user, Freezer $freezer): bool
    {
        return $freezer->isOwnedBy($user);
    }
}
