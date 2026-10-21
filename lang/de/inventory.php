<?php

/*
 | The inventory: every item of every freezer the user may access, with search,
 | the filter panel behind the filter button and the order of the list.
 |
 | The keys of "sort" are what the page sends (see InventoryItem::SORT_*), the
 | words are what the buttons show. A word may be shorter than its key: "Nach
 | MHD" is the key "best_before_asc", because the key names the rule (soonest
 | first) and the button only has to name the order.
 |
 | The keys of "best_before_groups" are the sections that order forms (see
 | InventoryItem::BEST_BEFORE_GROUP_*), the words are the headings above them.
 */

return [
    'title' => 'Inventar',
    'all_freezers' => 'Alle Gefrierschränke',
    'search_placeholder' => 'Artikel suchen …',
    'search_aria' => 'Artikel suchen',
    'filter_aria' => 'Filter',
    'filter' => 'Filter',
    'reset_filters' => 'Filter zurücksetzen',
    'all_categories' => 'Alle Kategorien',
    'single_freezer' => 'Nur ein Gefrierschrank – nach der Kategorie kannst du trotzdem filtern.',
    'nothing_found' => 'Dazu wurde nichts gefunden.',
    'show_all' => 'Alle Artikel zeigen',
    'to_freezer' => 'Zum Gefrierschrank',
    'empty' => 'Das Inventar ist noch leer.',

    'sort' => [
        'label' => 'Sortierung',
        'alphabetical' => 'Name (A–Z)',
        'category' => 'Kategorie',
        'best_before_asc' => 'Nach MHD',
    ],

    /* The sections the order by best before date forms: what has to be used
       within the next month, what within the next six months, what keeps
       longer - and, last, the items without a date. */
    'best_before_groups' => [
        'month' => 'Aufzubrauchen innerhalb 1 Monats',
        'six_months' => 'Aufzubrauchen innerhalb 6 Monate',
        'longer' => 'Noch länger haltbar',
        'none' => 'Kein MHD hinterlegt',
    ],
];
