<?php

/*
 | The inventory: every item of every freezer the user may access, with search,
 | the filter panel behind the filter button and the order of the list.
 |
 | The keys of "sort" are what the page sends (see InventoryItem::SORT_*), the
 | words are what the buttons show. A word may be shorter than its key: "Per
 | scadenza" is the key "best_before_asc", because the key names the rule
 | (soonest first) and the button only has to name the order.
 |
 | The keys of "best_before_groups" are the sections that order forms (see
 | InventoryItem::BEST_BEFORE_GROUP_*), the words are the headings above them.
 */

return [
    'title' => 'Inventario',
    'all_freezers' => 'Tutti i congelatori',
    'search_placeholder' => 'Cerca articoli …',
    'search_aria' => 'Cerca articoli',
    'filter_aria' => 'Filtro',
    'filter' => 'Filtro',
    'reset_filters' => 'Azzera i filtri',
    'all_categories' => 'Tutte le categorie',
    'single_freezer' => 'Un solo congelatore – puoi comunque filtrare per categoria.',
    'nothing_found' => 'Non è stato trovato nulla.',
    'show_all' => 'Mostra tutti gli articoli',
    'to_freezer' => 'Vai al congelatore',
    'empty' => 'L\'inventario è ancora vuoto.',

    'sort' => [
        'label' => 'Ordinamento',
        'alphabetical' => 'Nome (A–Z)',
        'category' => 'Categoria',
        'best_before_asc' => 'Per scadenza',
    ],

    /* The sections the order by best before date forms: what has to be used
       within the next month, what within the next six months, what keeps
       longer - and, last, the items without a date. */
    'best_before_groups' => [
        'month' => 'Da consumare entro 1 mese',
        'six_months' => 'Da consumare entro 6 mesi',
        'longer' => 'Si conserva più a lungo',
        'none' => 'Nessuna scadenza',
    ],
];
