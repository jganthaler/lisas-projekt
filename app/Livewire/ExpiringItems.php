<?php

namespace App\Livewire;

use App\Livewire\Concerns\FollowsOfflineChanges;
use App\Livewire\Concerns\InteractsWithItems;
use App\Models\InventoryItem;
use App\Support\FreezerContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The MHD watch list: everything that is expired or expires within the warning
 * window - across every freezer the user may access, not just the one that is
 * selected on the start page. Sorted by the best before date, so the most
 * urgent item comes first; every row names the freezer it sits in.
 */
#[Layout('layouts.app')]
#[Title('expiring.title')]
class ExpiringItems extends Component
{
    use FollowsOfflineChanges;
    use InteractsWithItems;

    public function render()
    {
        $user = auth()->user();

        // Makes sure a brand new account owns a freezer before it is listed.
        FreezerContext::current($user);

        $freezers = $user->freezers()->get();

        $items = InventoryItem::query()
            ->whereHas('drawer', fn (Builder $query) => $query->whereIn('freezer_id', $freezers->modelKeys()))
            ->with('drawer.freezer')
            ->withBestBefore()
            ->whereDate('best_before', '<=', Carbon::today()->addDays(InventoryItem::WARNING_DAYS)->toDateString())
            ->orderBy('best_before')
            ->orderBy('name_sort')
            ->get();

        return view('livewire.expiring-items', [
            'expired' => $items->filter->isExpired()->values(),
            'expiringSoon' => $items->reject->isExpired()->values(),
            'warningDays' => InventoryItem::WARNING_DAYS,
        ]);
    }
}
