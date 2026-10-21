<?php

namespace App\Livewire;

use App\Livewire\Concerns\FollowsOfflineChanges;
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
 * and the shared ones at the same time. Searchable, and in one of three orders:
 * by name, by category or by the best before date - soonest first (see
 * InventoryItem::sortedBy()). Every order sorts on the pre-calculated
 * "name_sort" column, so German umlauts stay where they belong. The freezer
 * filter, the category filter and the order sit behind the filter button next
 * to the search field, so the list stays uncluttered.
 *
 * The order by category is the one that groups: every category becomes a
 * heading of its own with its items alphabetically underneath, and an item
 * without a category is read as "Sonstiges" (see groups()). The other orders
 * keep the one flat list they have always been.
 */
#[Layout('layouts.app')]
#[Title('inventory.title')]
class InventoryOverview extends Component
{
    use FollowsOfflineChanges;
    use InteractsWithItems;

    public string $search = '';

    public string $freezerFilter = '';

    /** Empty means "every category". */
    public string $categoryFilter = '';

    /**
     * The order of the list - one of InventoryItem::SORT_*. Without a choice
     * the list is alphabetical, which is what a reader expects to find.
     */
    public string $sort = InventoryItem::SORT_ALPHABETICAL;

    /** Whether the filter panel behind the filter button is open. */
    public bool $showFilters = false;

    /**
     * Back to the whole inventory: the search and both filters.
     *
     * The empty state of the list offers this, so a search that found nothing
     * (a typo, a filter that is still set) is one tap away from the full list
     * again. "clearFilterSelection" is the quieter brother next to it: it keeps
     * what was searched for and only drops the filters.
     */
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

    /**
     * Put the same list in another order. An unknown order - just like an
     * unknown category - changes nothing: the list stays alphabetical.
     *
     * The order by category and the one by best before date group the list: the
     * items stand under the heading of their category - of their window of
     * remaining shelf life (see groups()).
     *
     * Sorting narrows nothing down, so it is no filter: the dot on the filter
     * button stays dark for it, and "Alle Artikel zeigen" keeps the order that
     * was picked - the list is not the reason why nothing was found.
     */
    public function sortBy(string $sort): void
    {
        $this->sort = InventoryItem::sortOrDefault($sort);
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
            ->sortedBy($this->sort)
            ->get();

        return view('livewire.inventory-overview', [
            'freezers' => $freezers,
            'selectedFreezer' => $selectedFreezer,
            'selectedCategory' => $selectedCategory,
            'categories' => InventoryItem::categoryLabels(),
            'sorts' => InventoryItem::sortLabels(),
            'items' => $items,
            // The list as the view shows it: under headings while the order by
            // category or by best before date is on, as one single list
            // otherwise (see groups()).
            'groupedByCategory' => $this->sort === InventoryItem::SORT_CATEGORY,
            'groupedByBestBefore' => $this->sort === InventoryItem::SORT_BEST_BEFORE_ASC,
            'groups' => $this->groups($items),
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

    /**
     * The list as the view shows it - one entry per heading.
     *
     * Two orders group the list: the one by category (a group per category, see
     * categoryGroups()) and the one by best before date (a group per window of
     * remaining shelf life, see bestBeforeGroups()). In every other order there
     * is one single group without a heading, so the view renders both shapes
     * through the same loop.
     *
     * @param  Collection<int, InventoryItem>  $items
     * @return Collection<int, array{key: string, label: string|null, items: Collection<int, InventoryItem>}>
     */
    private function groups(Collection $items): Collection
    {
        return match ($this->sort) {
            InventoryItem::SORT_CATEGORY => $this->categoryGroups($items),
            InventoryItem::SORT_BEST_BEFORE_ASC => $this->bestBeforeGroups($items),
            default => collect([[
                'key' => 'all',
                'label' => null,
                'items' => $items->values(),
            ]]),
        };
    }

    /**
     * One group per category, in the order the interface offers the categories
     * (the chips of the filter panel); a category without items is left out, so
     * no heading stands over nothing. An item without a category is read as
     * "Sonstiges" - the group exists only here, while reading: nothing is
     * written back to the database, and an item that gets a category later
     * simply appears in that group (the grouping follows the data of every
     * call).
     *
     * Every group is sorted on its own, so the order of one category is
     * independent of the others. The order of the query does not survive that:
     * the items without a category close the list there (see
     * InventoryItem::sortedBy()), and they have to stand among the items of
     * "Sonstiges" here.
     *
     * @param  Collection<int, InventoryItem>  $items
     * @return Collection<int, array{key: string, label: string, items: Collection<int, InventoryItem>}>
     */
    private function categoryGroups(Collection $items): Collection
    {
        $groups = [];

        foreach (InventoryItem::categoryLabels() as $key => $label) {
            $groups[$key] = ['key' => $key, 'label' => $label, 'items' => []];
        }

        foreach ($items as $item) {
            // An unknown category is no category at all (see
            // InventoryItem::categoryLabel()): it belongs to "Sonstiges".
            $key = array_key_exists((string) $item->category, $groups)
                ? $item->category
                : InventoryItem::CATEGORY_SONSTIGES;

            $groups[$key]['items'][] = $item;
        }

        return collect($groups)
            ->map(function (array $group): array {
                $group['items'] = collect($group['items'])
                    ->sortBy([['name_sort', 'asc'], ['id', 'asc']])
                    ->values();

                return $group;
            })
            ->filter(fn (array $group): bool => $group['items']->isNotEmpty())
            ->values();
    }

    /**
     * One group per window of the remaining shelf life, in the order the list
     * has to be read: what has to be used within the next month, what within
     * the next six months, what keeps longer - and, as the last one, the items
     * without a date (see InventoryItem::bestBeforeGroup()). A window without
     * items is left out, so no heading stands over nothing, and a date that is
     * corrected later moves its item into the other window with the next render
     * (the grouping follows the data of every call).
     *
     * Every group is sorted on its own - by date, so the top of a window is
     * what has to be eaten first; the items without a date have no date to sort
     * by and stay alphabetical among themselves. The order of the query says
     * the same (see InventoryItem::sortedBy()), but the grouping does not
     * depend on it.
     *
     * @param  Collection<int, InventoryItem>  $items
     * @return Collection<int, array{key: string, label: string, items: Collection<int, InventoryItem>}>
     */
    private function bestBeforeGroups(Collection $items): Collection
    {
        $groups = [];

        foreach (InventoryItem::bestBeforeGroupLabels() as $key => $label) {
            $groups[$key] = ['key' => $key, 'label' => $label, 'items' => []];
        }

        foreach ($items as $item) {
            $groups[$item->bestBeforeGroup()]['items'][] = $item;
        }

        return collect($groups)
            ->map(function (array $group): array {
                $group['items'] = collect($group['items'])
                    ->sortBy([
                        ['best_before', 'asc'],
                        ['name_sort', 'asc'],
                        ['id', 'asc'],
                    ])
                    ->values();

                return $group;
            })
            ->filter(fn (array $group): bool => $group['items']->isNotEmpty())
            ->values();
    }
}
