<?php

/*
 | One single drawer: everything inside it and the form that fills it.
 |
 | The category selection is shared with the inventory filter, its entries come
 | from items.php.
 */

return [
    'title' => 'Drawer',
    'back_aria' => 'Back to the freezer',
    'add_heading' => 'Add an item',
    'name_label' => 'What goes in?',
    'name_placeholder' => 'e.g. peas',
    'best_before_optional' => 'Best before (optional)',

    /*
     | The quick choices below the best before field: a tap puts the date that
     | far ahead of today. The keys are the ones the calculation works with
     | (InventoryItem::BEST_BEFORE_PRESETS) and have to stay identical to them.
     */
    'quick_best_before' => [
        'label' => 'Quick choice',
        '3_months' => '3 months',
        '6_months' => '6 months',
        '1_year' => '1 year',
        '2_years' => '2 years',
    ],

    'category_optional' => 'Category (optional)',
    'no_category' => 'Without a category',
    'add' => 'Add',
    'contents' => 'Contents',
    'item_label' => 'Item',
    'empty' => 'This drawer is still empty.',
    'item_added' => 'Item added.',
    'item_updated' => 'Item updated.',

    /*
     | Moving an item: it keeps everything it has and only changes its place -
     | into another drawer or into a drawer of another freezer of the household.
     | The choice offers every drawer of the household, grouped by freezer,
     | because two freezers may both have a "Schublade 1".
     */
    'move_heading' => 'Move item',
    'move_target_label' => 'New drawer',
    'move_target_placeholder' => 'Choose a drawer',
    'move_submit' => 'Move',
    'move_none' => 'There is no other drawer and no other freezer yet.',
    'move_invalid' => 'This drawer does not exist.',
    'item_moved' => '“:name” now lies in “:drawer” (“:freezer”).',
];
