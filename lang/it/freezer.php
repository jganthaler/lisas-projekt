<?php

/*
 | Both sides of the freezer: the closed start page with the list of all
 | freezers and the opened one where the drawers are managed.
 |
 | "default_name" and "drawer_name" are the names a brand new freezer and its
 | drawers get - they are stored, so they carry the language of the moment the
 | account was created.
 |
 | "summary" is filled with the two counters of common.php.
 */

return [
    'title' => 'Congelatore',
    'open_title' => 'Congelatore aperto',
    'back' => 'Indietro',
    'close_aria' => 'Chiudi il congelatore',
    'default_name' => 'Il mio congelatore',
    'drawer_name' => 'Cassetto :position',
    'empty' => 'Qui non c\'è ancora niente di congelato.',
    'summary' => ':items in :drawers',
    'mine' => 'I miei congelatori',
    'add_aria' => 'Aggiungi congelatore',
    'add_cancel_aria' => 'Annulla la creazione',
    'new_label' => 'Nuovo congelatore',
    'new_placeholder' => 'es. il freezer in cantina',
    'rename_label' => 'Nome del congelatore',
    'rename_aria' => 'Rinomina il congelatore',
    'delete_aria' => 'Elimina il congelatore',
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
    'rename_drawer_aria' => 'Rinomina il cassetto',
    'delete_drawer_aria' => 'Elimina il cassetto',
    'delete_drawer_confirm_title' => 'Eliminare il cassetto?',
    'delete_drawer_confirm_message' => '“:name” viene rimosso. Funziona solo finché è vuoto.',
    'no_drawers' => 'Nessun cassetto creato.',
    'drawer_added' => 'Cassetto aggiunto.',
    'drawer_renamed' => 'Cassetto rinominato.',
    'drawer_deleted' => 'Cassetto eliminato.',
    'drawer_name_required' => 'Dai un nome al cassetto.',
    'drawer_not_empty' => 'Il cassetto non è ancora vuoto. Rimuovi prima tutti gli articoli.',
];
