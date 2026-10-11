<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithItems;
use App\Models\Freezer;
use App\Models\InventoryItem;
use App\Support\FreezerContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The complete inventory of every freezer the user may access - the own ones
 * and the shared ones at the same time. Searchable and always sorted
 * alphabetically (locale safe through name_sort). The freezer filter sits
 * behind the filter button next to the search field, so the list stays
 * uncluttered.
 */
#[Layout('layouts.app')]
#[Title('Inventar')]
class InventoryOverview extends Component
{
    use InteractsWithItems;

    public string $search = '';

    public string $freezerFilter = '';

    /** Whether the filter panel behind the filter button is open. */
    public bool $showFilters = false;

    public function clearFilters(): void
    {
        $this->reset('search', 'freezerFilter');
    }

    public function toggleFilters(): void
    {
        $this->showFilters = ! $this->showFilters;
    }

    /**
     * Back to the inventory of every freezer, but keep what was searched for.
     */
    public function clearFilterSelection(): void
    {
        $this->reset('freezerFilter');
    }

    /**
     * Narrow the list down to one freezer.
     */
    public function filterByFreezer(string $freezerId): void
    {
        $this->freezerFilter = $freezerId;
    }

    public function render()
    {
        $user = auth()->user();

        // Makes sure a brand new account owns a freezer before it is listed.
        FreezerContext::current($user);

        // Only the names are needed: the freezer filter has no drawer level.
        $freezers = $user->freezers()
            ->orderBy('name')
            ->get();

        // An unknown or foreign id simply means "all freezers".
        $selectedFreezer = $freezers->firstWhere('id', (int) $this->freezerFilter);
        $search = trim($this->search);

        $items = $this->items($freezers)
            ->when($selectedFreezer !== null, fn (Builder $query) => $query->whereHas(
                'drawer',
                fn (Builder $drawer) => $drawer->where('freezer_id', $selectedFreezer->getKey()),
            ))
            ->when($search !== '', fn (Builder $query) => $query->where('name', 'like', '%'.$search.'%'))
            ->alphabetical()
            ->get();

        return view('livewire.inventory-overview', [
            'freezers' => $freezers,
            'selectedFreezer' => $selectedFreezer,
            'items' => $items,
            'totalCount' => $this->items($freezers)->count(),
            'isFiltered' => $search !== '' || $selectedFreezer !== null,
            // Lights up the dot on the filter button.
            'hasFilterSelection' => $selectedFreezer !== null,
        ]);
    }

    /**
     * Items of every freezer the user may access - and of nothing else.
     *
     * @param  Collection<int, Freezer>  $freezers
     */
    private function items(Collection $freezers): Builder
    {
        return InventoryItem::query()
            ->whereHas('drawer', fn (Builder $query) => $query->whereIn('freezer_id', $freezers->modelKeys()))
            ->with('drawer.freezer');
    }
}
