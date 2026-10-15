<?php

/*
 | A single item: the row that shows it in every list, the state of its best
 | before date and the categories it can be sorted into.
 |
 | The keys of "categories" are the slugs that are stored in the database
 | (see InventoryItem::CATEGORY_*), never their labels.
 */

return [
    'delete_aria' => 'Delete item',
    'edit_aria' => 'Edit item',
    'increase_aria' => 'Increase quantity',
    'decrease_aria' => 'Decrease quantity',
    'confirm_delete' => 'Do you really want to delete “:name”?',
    'deleted' => 'Item deleted.',

    'state' => [
        'expired' => 'expired',
        'soon' => 'expiring soon',
        'fresh' => 'fresh',
        'unknown' => 'without a best before date',
    ],

    'mhd' => [
        'none' => 'no best before date',
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
        'obst' => 'Fruit',
        'sonstiges' => 'Other',
    ],
];
