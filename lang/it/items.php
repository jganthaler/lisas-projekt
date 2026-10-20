<?php

/*
 | A single item: the row that shows it in every list, the state of its best
 | before date and the categories it can be sorted into.
 |
 | The keys of "categories" are the slugs that are stored in the database
 | (see InventoryItem::CATEGORY_*), never their labels.
 */

return [
    'delete_aria' => 'Elimina l\'articolo',
    'edit_aria' => 'Modifica l\'articolo',
    'move_aria' => 'Sposta l\'articolo',
    'increase_aria' => 'Aumenta la quantità',
    'decrease_aria' => 'Riduci la quantità',
    'delete_confirm_title' => 'Eliminare l’articolo?',
    'delete_confirm_message' => 'Vuoi davvero eliminare “:name”?',
    'deleted' => 'Articolo eliminato.',
    'open_in_drawer_aria' => 'Modifica l’articolo in “:drawer”',

    'state' => [
        'expired' => 'scaduto',
        'soon' => 'in scadenza',
        'fresh' => 'fresco',
        'unknown' => 'senza scadenza',
    ],

    'mhd' => [
        'none' => 'nessuna scadenza',
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
