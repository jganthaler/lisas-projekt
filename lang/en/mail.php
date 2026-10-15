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
        'subject' => 'Please confirm your email address',
        'greeting' => 'Hello :name!',
        'line' => 'please confirm your email address so that you can use :app.',
        'action' => 'Confirm the email address',
        'expiry' => 'The link is valid for one hour.',
        'ignore' => 'If you did not create an account, you can simply ignore this email.',
        'salutation' => 'Best regards from :app',
    ],

    'reset' => [
        'subject' => 'Reset your password',
        'greeting' => 'Hello :name!',
        'line' => 'you requested a new password for :app.',
        'action' => 'Choose a new password',
        'expiry' => 'The link is valid for :minutes minutes.',
        'ignore' => 'If you did not request this, you can simply ignore this email.',
        'salutation' => 'Best regards from :app',
    ],
];
