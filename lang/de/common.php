<?php

/*
 | Words and actions that appear on more than one screen.
 |
 | Every text of the app lives in lang/<language>/..., one directory per
 | language (see App\Support\Locales). The German files are the source of
 | truth: their wording is what the tests assert.
 |
 | The two counters are used with trans_choice(), so the number arrives through
 | the ":count" placeholder.
 */

return [
    'name' => 'Name',
    'email' => 'E-Mail',
    'password' => 'Passwort',
    'password_confirmation' => 'Passwort wiederholen',
    'show_password' => 'Passwort anzeigen',
    'hide_password' => 'Passwort verbergen',
    'quantity' => 'Menge',
    'category' => 'Kategorie',
    'best_before' => 'MHD',
    'save' => 'Speichern',
    'cancel' => 'Abbrechen',
    'edit' => 'Bearbeiten',
    'delete' => 'Löschen',
    'delete_forever' => 'Endgültig löschen',
    'move' => 'Verschieben',
    'create' => 'Anlegen',
    'logout' => 'Abmelden',
    'copy' => 'Kopieren',
    'copied' => 'Kopiert',
    'copy_failed' => 'Bitte markieren',
    'busy' => 'Einen Moment …',
    'sending' => 'Wird gesendet …',
    'saving' => 'Wird gespeichert …',
    'unknown' => 'Unbekannt',
    'someone' => 'Jemand',
    'this_person' => 'Diese Person',
    'items' => ':count Artikel',
    'drawers' => ':count Schublade|:count Schubladen',
];
