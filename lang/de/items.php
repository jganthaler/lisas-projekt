<?php

/*
 | A single item: the row that shows it in every list, the state of its best
 | before date and the categories it can be sorted into.
 |
 | The keys of "categories" are the slugs that are stored in the database
 | (see InventoryItem::CATEGORY_*), never their labels.
 */

return [
    'delete_aria' => 'Artikel löschen',
    'edit_aria' => 'Artikel bearbeiten',
    'increase_aria' => 'Menge erhöhen',
    'decrease_aria' => 'Menge verringern',
    'confirm_delete' => 'Möchtest du „:name“ wirklich löschen?',
    'deleted' => 'Artikel gelöscht.',

    'state' => [
        'expired' => 'abgelaufen',
        'soon' => 'bald abgelaufen',
        'fresh' => 'frisch',
        'unknown' => 'ohne MHD',
    ],

    'mhd' => [
        'none' => 'kein MHD',
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
        'obst' => 'Obst',
        'sonstiges' => 'Sonstiges',
    ],
];
