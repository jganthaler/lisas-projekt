<?php

/*
 | One single drawer: everything inside it and the form that fills it.
 |
 | The category selection is shared with the inventory filter, its entries come
 | from items.php.
 */

return [
    'title' => 'Schublade',
    'back_aria' => 'Zurück zum Gefrierschrank',
    'add_heading' => 'Artikel hinzufügen',
    'add_aria' => 'Artikel hinzufügen',
    'add_cancel_aria' => 'Hinzufügen abbrechen',
    'name_label' => 'Was kommt rein?',
    'name_placeholder' => 'z. B. Erbsen',
    'best_before_optional' => 'MHD (optional)',

    /*
     | The quick choices below the best before field: a tap puts the date that
     | far ahead of today. The keys are the ones the calculation works with
     | (InventoryItem::BEST_BEFORE_PRESETS) and have to stay identical to them.
     */
    'quick_best_before' => [
        'label' => 'MHD Schnellauswahl',
        '3_months' => '3 Monate',
        '6_months' => '6 Monate',
        '1_year' => '1 Jahr',
        '2_years' => '2 Jahre',
    ],

    'category_optional' => 'Kategorie (optional)',
    'no_category' => 'Ohne Kategorie',
    'add' => 'Hinzufügen',
    'contents' => 'Inhalt',
    'item_label' => 'Artikel',
    'empty' => 'Diese Schublade ist noch leer.',
    'item_added' => 'Artikel hinzugefügt.',
    'item_updated' => 'Artikel aktualisiert.',

    /*
     | Moving an item: it keeps everything it has and only changes its place -
     | into another drawer or into a drawer of another freezer of the household.
     | The choice offers every drawer of the household, grouped by freezer,
     | because two freezers may both have a "Schublade 1".
     */
    'move_heading' => 'Artikel verschieben',
    'move_target_label' => 'Neues Fach',
    'move_target_placeholder' => 'Fach wählen',
    'move_submit' => 'Verschieben',
    'move_none' => 'Es gibt noch kein anderes Fach und keinen anderen Gefrierschrank.',
    'move_invalid' => 'Dieses Fach gibt es nicht.',
    'item_moved' => '„:name“ liegt jetzt in „:drawer“ („:freezer“).',
];
