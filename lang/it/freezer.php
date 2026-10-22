<?php

/*
 | Both sides of the freezer: the closed start page with the list of all
 | freezers and the opened one where the drawers are managed.
 |
 | "default_name" and "drawer_name" are the names a brand new freezer and its
 | drawers get - they are stored, so they carry the language of the moment the
 | account was created.
 */

return [
    'title' => 'Congelatore',
    'open_title' => 'Congelatore aperto',
    'back' => 'Indietro',
    'close_aria' => 'Chiudi il congelatore',
    'default_name' => 'Il mio congelatore',
    'drawer_name' => 'Cassetto :position',
    'mine' => 'I miei congelatori',
    /*
     | The field "I miei congelatori" on the start page: its header carries the
     | title, the plus for another freezer and the menu ("⋮") of the active one,
     | which renames and deletes it. The arrow beside the name of the active
     | freezer opens the list of all of them.
     */
    'add' => 'Aggiungi congelatore',
    'edit' => 'Modifica congelatore',
    'delete' => 'Elimina congelatore',
    'menu_aria' => 'Altre azioni per “:name”',
    'new_label' => 'Nuovo congelatore',
    'new_placeholder' => 'es. il freezer in cantina',
    'rename_label' => 'Nome del congelatore',
    'delete_confirm_title' => 'Eliminare il congelatore?',
    'delete_confirm_message' => '“:name” scompare per sempre con tutti i cassetti e gli articoli.',
    'owned' => 'Il tuo congelatore',
    'shared' => 'Condiviso con te',
    'active' => 'attivo',
    'drawers_heading' => 'Cassetti',
    'manage_drawers' => 'Gestisci i cassetti',
    'name_required' => 'Dai un nome al congelatore.',
    'created' => '“:name” è stato creato.',
    'renamed' => 'Congelatore rinominato.',
    'deleted' => '“:name” è stato eliminato con tutti i cassetti e gli articoli.',
    'new_drawer' => 'Nuovo cassetto',
    'new_drawer_placeholder' => 'es. verdure',
    'add_drawer_aria' => 'Aggiungi cassetto',
    'rename_drawer_label' => 'Nome del cassetto',

    /*
     | The menu of a drawer row ("⋮"): its two actions carry their own label, and
     | the button of the menu names the row it belongs to.
     */
    'rename_drawer' => 'Rinomina',
    'drawer_menu_aria' => 'Altre azioni per “:name”',
    'delete_drawer_confirm_title' => 'Eliminare il cassetto?',
    'delete_drawer_confirm_message' => '“:name” viene rimosso. Funziona solo finché è vuoto.',
    'no_drawers' => 'Nessun cassetto creato.',
    'drawer_added' => 'Cassetto aggiunto.',
    'drawer_renamed' => 'Cassetto rinominato.',
    'drawer_deleted' => 'Cassetto eliminato.',
    'drawer_name_required' => 'Dai un nome al cassetto.',
    'drawer_not_empty' => 'In “:name” ci sono ancora articoli. Rimuovili prima.',
];
