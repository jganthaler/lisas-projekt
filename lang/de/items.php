<?php

/*
 | A single item: the row that shows it in every list, the state of its best
 | before date and the categories it can be sorted into.
 |
 | The keys of "categories" are the slugs that are stored in the database
 | (see InventoryItem::CATEGORY_*), never their labels.
 */

return [
    'increase_aria' => 'Menge erhöhen',
    'decrease_aria' => 'Menge verringern',
    'delete_confirm_title' => 'Artikel löschen?',
    'delete_confirm_message' => 'Möchtest du „:name“ wirklich löschen?',
    'deleted' => 'Artikel gelöscht.',
    'open_in_drawer_aria' => 'Artikel in „:drawer“ bearbeiten',
    'menu_aria' => 'Weitere Aktionen für „:name“',

    'state' => [
        'expired' => 'abgelaufen',
        'soon' => 'bald abgelaufen',
        'fresh' => 'frisch',
        'unknown' => 'ohne MHD',
    ],

    /*
     | The wording of the best before date: its relative form ("MHD in 3 Tagen")
     | and, under "none", the one that stands where a row would name the date
     | ("Kein MHD") - the place of the date is never left empty (see
     | resources/views/components/item-row.blade.php).
     */
    'mhd' => [
        'none' => 'Kein MHD',
        'today' => 'MHD heute',
        'tomorrow' => 'MHD morgen',
        'yesterday' => 'MHD seit gestern',
        'days_ago' => 'MHD vor :count Tagen',
        'in_days' => 'MHD in :count Tagen',
    ],

    'categories' => [
        'fleisch' => 'Fleisch',
        'fisch' => 'Fisch',
        'gemuese' => 'Gemüse',
        'suppe' => 'Suppe',
        'sosse' => 'Soßen',
        'obst' => 'Obst',
        'sonstiges' => 'Sonstiges',
    ],
];
