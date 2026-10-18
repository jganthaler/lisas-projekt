<?php

/*
 | The profile of the signed in user, including the language of the app.
 |
 | The names of the languages are not translated: "Deutsch" has to be
 | recognisable no matter which language the app is shown in.
 */

return [
    'title' => 'Profil',
    'subtitle' => 'Deine Angaben und dein Profilbild',
    'avatar' => 'Profilbild',
    'avatar_preview' => 'Vorschau',
    'choose_image' => 'Bild auswählen',
    'image_hint' => 'JPG, PNG oder WebP · bis 10 MB, größere Bilder werden verkleinert',
    'uploading' => 'Wird hochgeladen …',
    'remove_avatar_title' => 'Profilbild entfernen?',
    'remove_avatar_message' => 'Dein Bild wird gelöscht – danach sind wieder deine Initialen zu sehen.',
    'details' => 'Angaben',
    'password_heading' => 'Passwort ändern',
    'current_password' => 'Aktuelles Passwort',
    'new_password' => 'Neues Passwort',
    'password_confirmation' => 'Neues Passwort wiederholen',
    'language_heading' => 'Sprache',
    'language_hint' => 'Die ganze App wird danach in dieser Sprache angezeigt.',
    'language_saved' => 'Sprache geändert.',

    // Die installierte App (PWA): Der Abschnitt steht direkt unter der Sprache
    // und erscheint erst, wenn der Browser die Installation anbietet oder ein
    // iPhone den Umweg über das Teilen-Menü braucht (resources/js/app.js).
    'install_heading' => 'App installieren',
    'install_hint' => 'Coolventory läuft auch vom Startbildschirm aus – ohne Adressleiste und mit dem Schneeflocken-Icon.',
    'install_ios' => 'Tippe unten auf „Teilen“ und dann auf „Zum Home-Bildschirm“.',
    'install_button' => 'Zum Startbildschirm hinzufügen',
    'saved' => 'Profil gespeichert.',
    'password_changed' => 'Passwort geändert.',
    'image_saved' => 'Profilbild gespeichert.',
    'image_removed' => 'Profilbild entfernt.',
    'image_failed' => 'Das Bild konnte nicht gespeichert werden.',
    'image_invalid' => 'Bitte wähle ein Bild im Format JPG, PNG oder WebP.',
    'image_too_large' => 'Das Bild ist größer als 10 MB. Bitte wähle ein kleineres Bild.',

    // Rechtliches: die Seiten des Impressums, der Datenschutzerklärung und der
    // Nutzungsbedingungen (resources/views/legal/*) und die Löschung des
    // Kontos, die Art. 17 DSGVO erfüllt.
    'legal_heading' => 'Rechtliches',
    'legal_hint' => 'Impressum, Datenschutzerklärung und Nutzungsbedingungen.',
    'delete_heading' => 'Konto löschen',
    'delete_hint' => 'Entfernt dein Konto, dein Profilbild und alle von dir angelegten Gefrierschränke samt Inhalt. Das lässt sich nicht rückgängig machen.',
    'delete_password' => 'Passwort bestätigen',
    'delete_button' => 'Konto endgültig löschen',
    'delete_confirm_title' => 'Konto wirklich löschen?',
    'delete_confirm_message' => 'Dein Konto und alle Daten darin werden gelöscht – auch deine eigenen Gefrierschränke samt Inhalt. Gefrierschränke, die andere mit dir geteilt haben, bleiben bestehen. Eine Löschung ist nicht umkehrbar.',
    'account_deleted' => 'Dein Konto wurde gelöscht.',
];
