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
 * alphabetically (locale safe through name_sort). The freezer and the category
 * filter sit behind the filter button next to the search field, so the list
 * stays uncluttered.
 */
#[Layout('layouts.app')]
#[Title('Inventar')]
class InventoryOverview extends Component
{
    use InteractsWithItems;

    public string $search = '';

    public string $freezerFilter = '';

    /** Empty means "every category". */
    public string $categoryFilter = '';

    /** Whether the filter panel behind the filter button is open. */
    public bool $showFilters = false;

    public function clearFilters(): void
    {
        $this->reset('search', 'freezerFilter', 'categoryFilter');
    }

    public function toggleFilters(): void
    {
        $this->showFilters = ! $this->showFilters;
    }

    /**
     * Back to the inventory of every freezer and every category, but keep what
     * was searched for.
     */
    public function clearFilterSelection(): void
    {
        $this->reset('freezerFilter', 'categoryFilter');
    }

    /**
     * Narrow the list down to one freezer.
     */
    public function filterByFreezer(string $freezerId): void
    {
        $this->freezerFilter = $freezerId;
    }

    /**
     * Narrow the list down to one category. An unknown category - just like an
     * unknown freezer - simply means "every category".
     */
    public function filterByCategory(string $category): void
    {
        $this->categoryFilter = in_array($category, InventoryItem::categoryKeys(), true) ? $category : '';
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
        $selectedCategory = $this->categoryFilter !== '' ? $this->categoryFilter : null;
        $search = trim($this->search);

        $items = $this->items($freezers)
            ->when($selectedFreezer !== null, fn (Builder $query) => $query->whereHas(
                'drawer',
                fn (Builder $drawer) => $drawer->where('freezer_id', $selectedFreezer->getKey()),
            ))
            ->when($selectedCategory !== null, fn (Builder $query) => $query->withCategory($selectedCategory))
            ->when($search !== '', fn (Builder $query) => $query->where('name', 'like', '%'.$search.'%'))
            ->alphabetical()
            ->get();

        return view('livewire.inventory-overview', [
            'freezers' => $freezers,
            'selectedFreezer' => $selectedFreezer,
            'selectedCategory' => $selectedCategory,
            'categories' => InventoryItem::categoryLabels(),
            'items' => $items,
            'totalCount' => $this->items($freezers)->count(),
            'isFiltered' => $search !== '' || $selectedFreezer !== null || $selectedCategory !== null,
            // Lights up the dot on the filter button.
            'hasFilterSelection' => $selectedFreezer !== null || $selectedCategory !== null,
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
