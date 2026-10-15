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
        'subject' => 'Conferma il tuo indirizzo e-mail',
        'greeting' => 'Ciao :name!',
        'line' => 'conferma il tuo indirizzo e-mail per poter usare :app.',
        'action' => 'Conferma l\'indirizzo e-mail',
        'expiry' => 'Il link è valido per un\'ora.',
        'ignore' => 'Se non hai creato un account, puoi semplicemente ignorare questa e-mail.',
        'salutation' => 'Un saluto da :app',
    ],

    'reset' => [
        'subject' => 'Reimposta la password',
        'greeting' => 'Ciao :name!',
        'line' => 'hai richiesto una nuova password per :app.',
        'action' => 'Scegli una nuova password',
        'expiry' => 'Il link è valido per :minutes minuti.',
        'ignore' => 'Se non l\'hai richiesto, puoi semplicemente ignorare questa e-mail.',
        'salutation' => 'Un saluto da :app',
    ],
];
