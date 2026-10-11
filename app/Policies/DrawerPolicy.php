<?php

namespace App\Policies;

use App\Models\Drawer;
use App\Models\Freezer;
use App\Models\User;

/**
 * Drawers follow the access rules of their freezer: every member may work with
 * them.
 */
class DrawerPolicy
{
    /**
     * Any member of the freezer may add a drawer to it.
     */
    public function create(User $user, Freezer $freezer): bool
    {
        return $user->isMemberOf($freezer);
    }

    public function view(User $user, Drawer $drawer): bool
    {
        return $user->isMemberOf($drawer->freezer);
    }

    public function update(User $user, Drawer $drawer): bool
    {
        return $user->isMemberOf($drawer->freezer);
    }

    public function delete(User $user, Drawer $drawer): bool
    {
        return $user->isMemberOf($drawer->freezer);
    }
}
