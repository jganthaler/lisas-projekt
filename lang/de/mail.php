<?php

/*
 | The two mails the app sends: the confirmation of the e-mail address and the
 | link for a new password.
 |
 | They are written in the language of the account, because Laravel renders a
 | notification inside the locale of its recipient (User::preferredLocale()).
 | ":app" is the name of the application, ":minutes" the lifetime of the reset
 | link from config/auth.php.
 */

return [
    'verification' => [
        'subject' => 'Bitte bestätige deine E-Mail-Adresse',
        'greeting' => 'Hallo :name!',
        'line' => 'bitte bestätige deine E-Mail-Adresse, damit du :app nutzen kannst.',
        'action' => 'E-Mail-Adresse bestätigen',
        'expiry' => 'Der Link ist eine Stunde lang gültig.',
        'ignore' => 'Wenn du kein Konto angelegt hast, kannst du diese E-Mail einfach ignorieren.',
        'salutation' => 'Viele Grüße von :app',
    ],

    'reset' => [
        'subject' => 'Passwort zurücksetzen',
        'greeting' => 'Hallo :name!',
        'line' => 'du hast ein neues Passwort für :app angefordert.',
        'action' => 'Neues Passwort wählen',
        'expiry' => 'Der Link ist :minutes Minuten lang gültig.',
        'ignore' => 'Wenn du das nicht angefordert hast, kannst du diese E-Mail einfach ignorieren.',
        'salutation' => 'Viele Grüße von :app',
    ],
];
