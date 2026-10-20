<?php

namespace App\Http\Controllers;

use App\Support\OfflineInventory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The two answers the offline page needs.
 *
 * The page itself is a static file (public/offline.html) without a server
 * around it, so these are the only two addresses it talks to: the inventory as
 * data and the changes it collected while there was no network. Both belong to
 * the session of the user - the page sends the CSRF token of the last visit
 * along, which resources/js/app.js keeps in the local storage for it.
 *
 * The rules behind them (what a snapshot contains, what an operation may do)
 * live in App\Support\OfflineInventory.
 */
class OfflineController extends Controller
{
    /**
     * The inventory of the user as data for the offline page.
     */
    public function snapshot(Request $request): JsonResponse
    {
        return response()->json(OfflineInventory::snapshot($request->user()));
    }

    /**
     * Take the changes of the offline page and answer with the inventory as it
     * stands afterwards. The answer is what the page stores as its new copy,
     * which is why the queue of the page is empty once this call succeeded.
     */
    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'operations' => ['present', 'array', 'max:'.OfflineInventory::MAX_OPERATIONS],
        ]);

        $result = OfflineInventory::apply($request->user(), $validated['operations']);

        return response()->json([
            'applied' => $result['applied'],
            'skipped' => $result['skipped'],
            'snapshot' => OfflineInventory::snapshot($request->user()),
        ]);
    }
}
