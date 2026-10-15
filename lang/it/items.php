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
    'increase_aria' => 'Aumenta la quantità',
    'decrease_aria' => 'Riduci la quantità',
    'confirm_delete' => 'Vuoi davvero eliminare “:name”?',
    'deleted' => 'Articolo eliminato.',

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
        'obst' => 'Frutta',
        'sonstiges' => 'Altro',
    ],
];
