<?php

/*
 | One single drawer: everything inside it and the form that fills it.
 |
 | The category selection is shared with the inventory filter, its entries come
 | from items.php.
 */

return [
    'title' => 'Cassetto',
    'back_aria' => 'Torna al congelatore',
    'add_heading' => 'Aggiungi un articolo',
    'add_aria' => 'Aggiungi un articolo',
    'add_cancel_aria' => 'Annulla l\'aggiunta',
    'name_label' => 'Cosa ci metti?',
    'name_placeholder' => 'es. piselli',
    'best_before_optional' => 'Scadenza (facoltativa)',

    /*
     | The quick choices below the best before field: a tap puts the date that
     | far ahead of today. The keys are the ones the calculation works with
     | (InventoryItem::BEST_BEFORE_PRESETS) and have to stay identical to them.
     */
    'quick_best_before' => [
        'label' => 'Scelta rapida della scadenza',
        '3_months' => '3 mesi',
        '6_months' => '6 mesi',
        '1_year' => '1 anno',
        '2_years' => '2 anni',
    ],

    'category_optional' => 'Categoria (facoltativa)',
    'no_category' => 'Senza categoria',
    'add' => 'Aggiungi',
    'contents' => 'Contenuto',
    'item_label' => 'Articolo',
    'empty' => 'Questo cassetto è ancora vuoto.',
    'item_added' => 'Articolo aggiunto.',
    'item_updated' => 'Articolo aggiornato.',

    /*
     | Moving an item: it keeps everything it has and only changes its place -
     | into another drawer or into a drawer of another freezer of the household.
     | The choice offers every drawer of the household, grouped by freezer,
     | because two freezers may both have a "Schublade 1".
     */
    'move_heading' => 'Sposta l\'articolo',
    'move_target_label' => 'Nuovo cassetto',
    'move_target_placeholder' => 'Scegli il cassetto',
    'move_submit' => 'Sposta',
    'move_none' => 'Non c\'è ancora un altro cassetto né un altro congelatore.',
    'move_invalid' => 'Questo cassetto non esiste.',
    'item_moved' => '“:name” ora è in “:drawer” (“:freezer”).',
];
