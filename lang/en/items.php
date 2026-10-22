<?php

/*
 | A single item: the row that shows it in every list, the state of its best
 | before date and the categories it can be sorted into.
 |
 | The keys of "categories" are the slugs that are stored in the database
 | (see InventoryItem::CATEGORY_*), never their labels.
 */

return [
    'increase_aria' => 'Increase quantity',
    'decrease_aria' => 'Decrease quantity',
    'delete_confirm_title' => 'Delete item?',
    'delete_confirm_message' => 'Do you really want to delete “:name”?',
    'deleted' => 'Item deleted.',
    'menu_aria' => 'More actions for “:name”',

    'state' => [
        'expired' => 'expired',
        'soon' => 'expiring soon',
        'fresh' => 'fresh',
        'unknown' => 'without a best before date',
    ],

    /*
     | The wording of the best before date: its relative form ("best before in 3
     | days") and, under "none", the one that stands where a row would name the
     | date ("No best before date") - the place of the date is never left empty
     | (see resources/views/components/item-row.blade.php).
     */
    'mhd' => [
        'none' => 'No best before date',
        'today' => 'best before today',
        'tomorrow' => 'best before tomorrow',
        'yesterday' => 'best before since yesterday',
        'days_ago' => 'best before :count days ago',
        'in_days' => 'best before in :count days',
    ],

    'categories' => [
        'fleisch' => 'Meat',
        'fisch' => 'Fish',
        'gemuese' => 'Vegetables',
        'suppe' => 'Soup',
        'sosse' => 'Sauces',
        'obst' => 'Fruit',
        'sonstiges' => 'Other',
    ],
];
