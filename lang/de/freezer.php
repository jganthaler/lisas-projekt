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
    'title' => 'Gefrierschrank',
    'open_title' => 'Gefrierschrank geöffnet',
    'back' => 'Zurück',
    'close_aria' => 'Gefrierschrank schließen',
    'default_name' => 'Mein Gefrierschrank',
    'drawer_name' => 'Schublade :position',
    'empty' => 'Hier ist noch nichts eingefroren.',
    'summary' => ':items in :drawers',
    'mine' => 'Meine Gefrierschränke',
    'add_aria' => 'Gefrierschrank hinzufügen',
    'add_cancel_aria' => 'Anlegen abbrechen',
    'new_label' => 'Neuer Gefrierschrank',
    'new_placeholder' => 'z. B. Truhe im Keller',
    'rename_label' => 'Name des Gefrierschranks',
    'rename_aria' => 'Gefrierschrank umbenennen',
    'delete_aria' => 'Gefrierschrank löschen',
    'delete_confirm_title' => 'Gefrierschrank löschen?',
    'delete_confirm_message' => '„:name“ verschwindet mit allen Schubladen und Artikeln für immer.',
    'owned' => 'Dein Gefrierschrank',
    'shared' => 'Mit dir geteilt',
    'active' => 'aktiv',
    'drawers_heading' => 'Schubladen',
    'manage_drawers' => 'Schubladen bearbeiten',
    'name_required' => 'Bitte gib dem Gefrierschrank einen Namen.',
    'created' => '„:name“ ist angelegt.',
    'renamed' => 'Gefrierschrank umbenannt.',
    'deleted' => '„:name“ wurde mit allen Schubladen und Artikeln gelöscht.',
    'new_drawer' => 'Neue Schublade',
    'new_drawer_placeholder' => 'z. B. Gemüse',
    'add_drawer_aria' => 'Schublade hinzufügen',
    'rename_drawer_label' => 'Name der Schublade',
    'rename_drawer_aria' => 'Schublade umbenennen',
    'delete_drawer_aria' => 'Schublade löschen',
    'delete_drawer_confirm_title' => 'Schublade löschen?',
    'delete_drawer_confirm_message' => '„:name“ wird entfernt. Das geht nur, solange sie leer ist.',
    'no_drawers' => 'Noch keine Schublade angelegt.',
    'drawer_added' => 'Schublade hinzugefügt.',
    'drawer_renamed' => 'Schublade umbenannt.',
    'drawer_deleted' => 'Schublade gelöscht.',
    'drawer_name_required' => 'Bitte gib der Schublade einen Namen.',
    'drawer_not_empty' => 'Die Schublade ist noch nicht leer. Bitte zuerst alle Artikel entfernen.',
];
