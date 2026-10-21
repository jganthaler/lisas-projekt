<?php

/*
 | A single item: the row that shows it in every list, the state of its best
 | before date and the categories it can be sorted into.
 |
 | The keys of "categories" are the slugs that are stored in the database
 | (see InventoryItem::CATEGORY_*), never their labels.
 */

return [
    'increase_aria' => 'Aumenta la quantità',
    'decrease_aria' => 'Riduci la quantità',
    'delete_confirm_title' => 'Eliminare l’articolo?',
    'delete_confirm_message' => 'Vuoi davvero eliminare “:name”?',
    'deleted' => 'Articolo eliminato.',
    'open_in_drawer_aria' => 'Modifica l’articolo in “:drawer”',
    'menu_aria' => 'Altre azioni per “:name”',

    'state' => [
        'expired' => 'scaduto',
        'soon' => 'in scadenza',
        'fresh' => 'fresco',
        'unknown' => 'senza scadenza',
    ],

    /*
     | The wording of the best before date: its relative form ("scade tra 3
     | giorni") and, under "none", the one that stands where a row would name
     | the date ("Nessuna scadenza") - the place of the date is never left empty
     | (see resources/views/components/item-row.blade.php).
     */
    'mhd' => [
        'none' => 'Nessuna scadenza',
        'today' => 'scade oggi',
        'tomorrow' => 'scade domani',
        'yesterday' => 'scaduto ieri',
        'days_ago' => 'scaduto :count giorni fa',
        'in_days' => 'scade tra :count giorni',
    ],

    'categories' => [
        'fleisch' => 'Carne',
        'fisch' => 'Pesce',
        'gemuese' => 'Verdure',
        'suppe' => 'Zuppa',
        'sosse' => 'Salse',
        'obst' => 'Frutta',
        'sonstiges' => 'Altro',
    ],
];
