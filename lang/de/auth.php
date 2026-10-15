<?php

/*
 | Signing in, registering, the password reset and the reminder that the
 | e-mail address still has to be confirmed.
 |
 | "title" is the name of the browser tab, "heading" the headline of the page.
 | The hint on the verification page is built around the address of the account
 | in the view, which is why it is split into "sent_before" and "sent_after".
 */

return [
    'tagline' => 'Gemeinsam den Überblick behalten.',

    'login' => [
        'title' => 'Anmelden',
        'heading' => 'Willkommen zurück',
        'subtitle' => 'Melde dich an, um deinen Gefrierschrank zu öffnen.',
        'remember' => 'Angemeldet bleiben',
        'submit' => 'Anmelden',
        'failed' => 'E-Mail oder Passwort stimmen nicht.',
        'throttled' => 'Zu viele Anmeldeversuche. Bitte versuche es in einer Minute erneut.',
        'no_account' => 'Noch kein Konto?',
        'register' => 'Jetzt registrieren',
    ],

    'register' => [
        'title' => 'Registrieren',
        'heading' => 'Konto anlegen',
        'subtitle' => 'Dein eigener Gefrierschrank mit drei Schubladen wartet schon auf dich.',
        'submit' => 'Registrieren',
        'busy' => 'Konto wird angelegt …',
        'have_account' => 'Schon registriert?',
        'login' => 'Zur Anmeldung',
        'status' => 'Fast geschafft! Wir haben dir einen Link an :email geschickt.',

        'consent' => 'Mit der Registrierung stimmst du den :terms und der :privacy zu.',
    ],

    'forgot' => [
        'title' => 'Passwort vergessen',
        'heading' => 'Passwort vergessen?',
        'subtitle' => 'Kein Problem: Gib deine E-Mail-Adresse ein, dann schicken wir dir einen Link zum Zurücksetzen. Er ist eine Stunde lang gültig.',
        'submit' => 'Link zusenden',
        'sent' => 'Wenn es ein Konto zu :email gibt, ist die E-Mail mit dem Link unterwegs. Schau auch im Spam-Ordner nach.',
        'hint' => 'Nichts angekommen? Dann warte einen Moment und fordere den Link erneut an.',
        'back_to_login' => 'Zurück zur Anmeldung',
        'throttled' => 'Zu viele Anfragen. Bitte versuche es in einer Minute erneut.',
        'already_requested' => 'Es wurde gerade schon ein Link angefordert. Bitte warte eine Minute.',
    ],

    'reset' => [
        'title' => 'Neues Passwort',
        'subtitle' => 'Wähle ein neues Passwort für dein Konto. Danach kannst du dich damit anmelden.',
        'submit' => 'Passwort speichern',
        'invalid_link' => 'Der Link ist ungültig oder abgelaufen. Bitte fordere einen neuen an.',
        'status' => 'Dein Passwort ist geändert. Melde dich jetzt neu an.',
        'expired_question' => 'Link abgelaufen?',
        'request_new' => 'Neuen anfordern',
    ],

    'verify' => [
        'title' => 'E-Mail bestätigen',
        'heading' => 'E-Mail-Adresse bestätigen',
        'sent_before' => 'Wir haben dir einen Link an',
        'sent_after' => 'geschickt. Bitte klicke darauf, um deinen Gefrierschrank freizuschalten.',
        'resend' => 'Link erneut senden',
        'throttled' => 'Bitte warte eine Minute, bevor du einen neuen Link anforderst.',
        'sent' => 'Wir haben dir einen neuen Link an :email geschickt.',
        'confirmed' => 'Deine E-Mail-Adresse ist bestätigt. Willkommen!',
    ],
];
