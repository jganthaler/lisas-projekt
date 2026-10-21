<?php

/*
 | The inventory: every item of every freezer the user may access, with search,
 | the filter panel behind the filter button and the order of the list.
 |
 | The keys of "sort" are what the page sends (see InventoryItem::SORT_*), the
 | words are what the buttons show. A word may be shorter than its key: "By
 | best before" is the key "best_before_asc", because the key names the rule
 | (soonest first) and the button only has to name the order.
 |
 | The keys of "best_before_groups" are the sections that order forms (see
 | InventoryItem::BEST_BEFORE_GROUP_*), the words are the headings above them.
 */

return [
    'title' => 'Inventory',
    'all_freezers' => 'All freezers',
    'search_placeholder' => 'Search items …',
    'search_aria' => 'Search items',
    'filter_aria' => 'Filter',
    'filter' => 'Filter',
    'reset_filters' => 'Reset filters',
    'all_categories' => 'All categories',
    'single_freezer' => 'Only one freezer – you can still filter by category.',
    'nothing_found' => 'Nothing was found for that.',
    'show_all' => 'Show all items',
    'to_freezer' => 'Go to the freezer',
    'empty' => 'The inventory is still empty.',

    'sort' => [
        'label' => 'Sorting',
        'alphabetical' => 'Name (A–Z)',
        'category' => 'Category',
        'best_before_asc' => 'By best before',
    ],

    /* The sections the order by best before date forms: what has to be used
       within the next month, what within the next six months, what keeps
       longer - and, last, the items without a date. */
    'best_before_groups' => [
        'month' => 'Use within 1 month',
        'six_months' => 'Use within 6 months',
        'longer' => 'Keeps longer',
        'none' => 'No best before date',
    ],
];
