<?php

namespace App\Support;

use App\Models\Freezer;
use App\Models\User;

/**
 * Resolves the freezer a user is currently working in.
 *
 * The selection lives in the session. If a user has access to several freezers
 * (their own one plus a shared one) the owned freezer wins, unless the active
 * freezer was chosen explicitly - for example right after accepting an
 * invitation.
 */
class FreezerContext
{
    public const SESSION_KEY = 'active_freezer_id';

    /**
     * The active freezer of the given user. Creates the initial freezer with
     * its three drawers on the very first access.
     */
    public static function current(User $user): Freezer
    {
        $freezer = self::fromSession($user);

        if ($freezer !== null) {
            return $freezer;
        }

        $freezer = $user->freezers()
            ->orderByRaw('CASE WHEN freezers.owner_id = ? THEN 0 ELSE 1 END', [$user->getKey()])
            ->orderBy('freezers.id')
            ->first();

        $freezer ??= Freezer::provisionFor($user);

        self::select($freezer);

        return $freezer;
    }

    /**
     * The freezer stored in the session - but only if the user may access it.
     */
    public static function fromSession(User $user): ?Freezer
    {
        $id = self::id();

        if ($id === null) {
            return null;
        }

        return $user->freezers()->whereKey($id)->first();
    }

    public static function id(): ?int
    {
        $id = session(self::SESSION_KEY);

        return $id === null ? null : (int) $id;
    }

    public static function select(Freezer $freezer): void
    {
        session([self::SESSION_KEY => $freezer->getKey()]);
    }

    public static function forget(): void
    {
        session()->forget(self::SESSION_KEY);
    }
}
