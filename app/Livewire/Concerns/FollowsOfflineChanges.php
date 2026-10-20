<?php

namespace App\Livewire\Concerns;

use Livewire\Attributes\On;

/**
 * The offline page writes its changes into the inventory while a page of the
 * app stands (see resources/js/app.js): as soon as those changes arrived, the
 * script dispatches "offline-synced" and every screen that lists items listens
 * here. The empty body is the point - a listener makes Livewire render the
 * component again, so the list shows what the offline changes did instead of
 * what the last page load saw.
 *
 * Without it a screen would keep a list that is older than the database, which
 * is exactly what the offline mode must not leave behind.
 */
trait FollowsOfflineChanges
{
    #[On('offline-synced')]
    public function reloadAfterOfflineSync(): void {}
}
