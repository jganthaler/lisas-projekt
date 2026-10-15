<?php

/*
 | The legal pages of the application: imprint, privacy policy and terms of
 | use.
 |
 | The texts describe what this application really does - which data is stored,
 | where it is kept, which services are involved and which cookie is set. The
 | details of the operator (and of hosting and mail) do not live here but in
 | config/legal.php, which reads them from the environment (LEGAL_*); the
 | placeholders ":operator", ":hosting", ":mail_provider" and ":date" are
 | filled in by App\Support\Legal before the page is rendered.
 |
 | Every document consists of "sections" - a heading, paragraphs and an optional
 | list - so the three languages keep the same structure
 | (resources/views/legal/*). The German files are the source of truth.
 |
 | These texts are templates: they cover the usual case of a private, free
 | household application and still have to be checked by the operator (see the
 | README, section "Rechtliches").
 */

return [
    'imprint' => [
        'title' => 'Imprint',
        'heading' => 'Imprint',
        'intro' => 'Who provides this application and how to reach them.',
        'provider_heading' => 'Information pursuant to § 5 DDG',
        'provider_note' => ':app is a private, free service for managing freezers together in a household. No goods and no services are sold through it.',
        'contact_heading' => 'Contact',
        'email_label' => 'Email',
        'phone_label' => 'Phone',
        'incomplete_heading' => 'Operator details are missing',
        'incomplete_body' => 'This page is not complete yet: no operator details are configured. Until they are added, the application is missing the imprint required by law. Fill in the LEGAL_* values from .env.example (see config/legal.php).',
        'missing_heading' => 'Missing:',
        'sections' => [
            [
                'heading' => 'Liability for content',
                'body' => [
                    'The content of this application comes from what its users enter: names, quantities and best-before dates of food are maintained by them. No guarantee is given for the accuracy, completeness or up-to-dateness of this information.',
                    'As a service provider we are not obliged to monitor transmitted or stored third-party information or to investigate circumstances that point to unlawful activity. Obligations to remove or block information under general law remain unaffected.',
                ],
            ],
            [
                'heading' => 'Liability for links',
                'body' => [
                    'This application does not contain links to third-party content. If users point to offers of others, their operators alone are responsible for them.',
                ],
            ],
            [
                'heading' => 'Consumer dispute resolution',
                'body' => [
                    'We are neither obliged nor willing to take part in dispute resolution proceedings before a consumer arbitration board.',
                ],
            ],
            [
                'heading' => 'Scope',
                'body' => [
                    'This imprint applies to :app at :url.',
                ],
            ],
        ],
    ],

    'privacy' => [
        'title' => 'Privacy',
        'heading' => 'Privacy policy',
        'intro' => 'What data :app stores, why, for how long – and what rights you have.',
        'controller_heading' => 'Controller',
        'controller_intro' => 'The controller for the processing of personal data in this application is:',
        'sections' => [
            [
                'heading' => 'The essentials',
                'body' => [
                    ':app is a private service for your own household. The application does without advertising, analytics tools and profiling.',
                ],
                'list' => [
                    'Only the data needed to run it is stored: your account, the contents of your freezers and technical session data.',
                    'Data is not sold and not passed on for advertising purposes.',
                    'Apart from the technically necessary sign-in cookie, only your colour choice (light or dark) is stored in the browser – which is why there is no cookie banner here.',
                    'You can delete your account yourself at any time in your profile.',
                ],
            ],
            [
                'heading' => 'Account and sign-in',
                'body' => [
                    'For every account we store a display name (voluntary; without it, the part before the @ of your email address is shown), your email address, your password (only as a hash, so it cannot be read), the language you chose, an optionally uploaded profile picture and the time your email address was confirmed.',
                    'We need this information to give you an account, to sign you in and to make sure that the email address really belongs to you. The legal basis is Art. 6(1)(b) GDPR.',
                ],
            ],
            [
                'heading' => 'Content of the application',
                'body' => [
                    'Freezers, drawers and items (name, quantity, category, best-before date) as well as memberships and invitations. This information is the very function of the application: it is visible to everyone who has access to the freezer in question – the owner and the people they invited.',
                    'An invitation link contains a random token. Only a hash of it is stored, not the link itself; it expires after seven days and can be used once. The legal basis is Art. 6(1)(b) GDPR.',
                ],
            ],
            [
                'heading' => 'Technical data',
                'body' => [
                    'When you sign in, the application creates a session. Stored are the session identifier (as a cookie in your browser), your IP address, the identifier of your browser (user agent), the time of the last activity and – if you choose "stay signed in" – a persistent sign-in token.',
                    'This data serves operation, security and the defence against misuse (Art. 6(1)(f) GDPR). Failed sign-in attempts are counted for a short time so that passwords cannot be tried out.',
                ],
            ],
            [
                'heading' => 'Logs of the hosting provider',
                'body' => [
                    'The hosting provider (:hosting) logs page requests in server log files: IP address, time, requested address, status code, amount of data transferred and browser identifier. This is necessary for technical operation, troubleshooting and the defence against attacks (Art. 6(1)(f) GDPR).',
                    'How long these logs are kept depends on the retention periods of the provider (usually a few days); the details are in their privacy policy.',
                ],
            ],
            [
                'heading' => 'Emails',
                'body' => [
                    'The application only sends emails that you trigger yourself: the confirmation of your email address after registration and the link to reset your password. For this, your email address is passed to the mailing service :mail_provider (Art. 6(1)(b) and (f) GDPR).',
                ],
            ],
            [
                'heading' => 'Legal bases',
                'body' => [
                    'Unless stated otherwise above, we process data on the basis of Art. 6(1)(b) GDPR (providing the application) and Art. 6(1)(f) GDPR (security and stability). Voluntary information such as the display name or the profile picture is processed on the basis of your consent (Art. 6(1)(a) GDPR); you can change or remove it at any time.',
                ],
                'note' => 'You can withdraw a consent you have given at any time (Art. 7(3) GDPR). The lawfulness of the processing carried out until then remains unaffected.',
            ],
            [
                'heading' => 'Cookies and local storage',
                'body' => [
                    'This application does not set cookies for advertising or analytics. A consent banner is therefore not required.',
                ],
                'list' => [
                    'laravel_session – technically necessary cookie for signing in. It is deleted when you sign out and expires after 120 minutes without activity (§ 25(2) no. 2 TDDDG).',
                    'A sign-in cookie ("stay signed in") – only if you make that choice in the sign-in form. It keeps you signed in after closing the browser.',
                    'color-scheme in the local storage of your browser – remembers whether you use the application in light or dark mode. The entry contains no identifier and is not evaluated.',
                ],
                'note' => 'You can delete these entries in your browser at any time; afterwards you only have to sign in again.',
            ],
            [
                'heading' => 'Recipients',
                'body' => [
                    'Your data stays within this application. It is not sold and not passed on for advertising purposes. Only the following have access to the data:',
                ],
                'list' => [
                    'Hosting: :hosting (location: :hosting_location). The provider processes database, files and logs on our behalf under Art. 28 GDPR.',
                    'Email delivery: :mail_provider (location: :mail_location) – exclusively for the confirmation and password emails.',
                    'People with access to a freezer: they see the contents of that freezer, because that is exactly what the application is for.',
                ],
                'note' => 'No transfer to countries outside the European Union takes place if the providers named above are based in the EU. Otherwise it is based on the standard contractual clauses of the European Commission.',
            ],
            [
                'heading' => 'Retention',
                'body' => [
                    'Data is stored for as long as it is needed for the respective purpose:',
                ],
                'list' => [
                    'Account (display name, email address, password hash, language): until you delete your account.',
                    'Profile picture: until you remove it or delete your account.',
                    'Freezers, drawers and items: until they are deleted or the account is deleted.',
                    'Sessions (IP address, browser identifier): until you sign out, at the latest after 120 minutes without activity.',
                    'Invitation links: seven days.',
                    'Email confirmation and password reset links: 60 minutes.',
                    'Password reset entries: until the link is used or a new one is requested.',
                    'Server logs of the hosting provider: according to their retention periods.',
                ],
            ],
            [
                'heading' => 'Your rights',
                'body' => [
                    'You have the following rights towards the controller:',
                ],
                'list' => [
                    'Access to the data processed (Art. 15 GDPR)',
                    'Rectification of inaccurate data (Art. 16 GDPR)',
                    'Erasure (Art. 17 GDPR)',
                    'Restriction of processing (Art. 18 GDPR)',
                    'Data portability (Art. 20 GDPR)',
                    'Objection to processing based on legitimate interests (Art. 21 GDPR)',
                ],
                'note' => 'For access, rectification or portability a message to :operator_email is enough. You may also lodge a complaint with a supervisory authority (Art. 77 GDPR) – for example the authority at your place of residence.',
            ],

            [
                'heading' => 'Deleting your account',
                'body' => [
                    'In your profile you will find the "delete account" button. For safety you have to enter your password for it. Deleted are the account, the profile picture, the sign-in sessions and all freezers you created, together with their drawers and items.',
                    'Freezers that others shared with you remain – you only lose access to them. A deletion cannot be undone. Alternatively, a message to :operator_email is enough.',
                ],
            ],
            [
                'heading' => 'Security',
                'body' => [
                    'The connection to the application is encrypted (HTTPS). Passwords are stored only as hashes and cannot be read even by the operator. Profile pictures are kept in a directory that is not publicly reachable and are only delivered to signed-in people.',
                ],
            ],
            [
                'heading' => 'No automated decision-making',
                'body' => [
                    'There is no profiling and no automated decision-making within the meaning of Art. 22 GDPR. The best-before traffic light of the application is a simple calculation from the date you stored.',
                ],
            ],
            [
                'heading' => 'Changes to this policy',
                'body' => [
                    'If the application changes, this policy is updated. The version published here applies; the date of the last change is shown at the bottom of the page.',
                ],
            ],
        ],
    ],

    'terms' => [
        'title' => 'Terms of use',
        'heading' => 'Terms of use',
        'intro' => 'The rules for using :app.',
        'sections' => [
            [
                'heading' => 'Scope',
                'body' => [
                    'These terms of use apply to the use of :app. The provider is the person named in the imprint. By registering you accept these terms.',
                ],
            ],
            [
                'heading' => 'What the application does',
                'body' => [
                    'The application helps you manage the contents of freezers together and reminds you of best-before dates. It is an aid and no assurance about the quality or edibility of food: names, quantities and best-before dates come from the users, and every person decides for themselves whether food is still eaten.',
                ],
            ],
            [
                'heading' => 'Account',
                'body' => [
                    'You need an account to use the application. The following applies:',
                ],
                'list' => [
                    'You provide an email address that belongs to you and confirm it via the link in the confirmation email.',
                    'You keep your password secret and inform the provider if you suspect that somebody else has access to your account.',
                    'One account per person; passing the account on to others is not allowed.',
                    'You must be at least 16 years old. Younger people may use the application only with the consent of their legal guardian.',
                    'Your details must be correct and up to date.',
                ],
            ],
            [
                'heading' => 'Rules for using the application',
                'body' => [
                    'When using the application:',
                ],
                'list' => [
                    'No unlawful, insulting or degrading content.',
                    'No attempts to disturb or overload the application, to read it out automatically or to circumvent access rights.',
                    'Invitation links are personal: whoever has the link gets access to the freezer. Therefore only pass it on to people you really want to invite.',
                    'No misuse of other accounts and no uploading of content you do not hold the rights to.',
                ],
            ],
            [
                'heading' => 'Availability',
                'body' => [
                    'The application is provided free of charge. There is no entitlement to permanent availability; maintenance, changes and even the discontinuation of the service are possible. Please note important information down elsewhere if you need it for longer.',
                ],
            ],

            [
                'heading' => 'Liability',
                'body' => [
                    'The provider is liable without limitation for intent and gross negligence, for injury to life, body or health and under the Product Liability Act. For simple negligence the provider is liable only for the breach of essential contractual duties and, in amount, limited to the foreseeable damage typical for this kind of contract. Beyond that, liability is excluded – in particular for damage resulting from decisions you make on the basis of the information stored in the application, and for data loss where the damage could have been avoided by a reasonable data backup.',
                ],
            ],
            [
                'heading' => 'Term and termination',
                'body' => [
                    'You can end the use at any time by deleting your account in your profile. The provider may block or delete an account if these terms are violated or if the security of the application requires it. Deleted data cannot be restored.',
                ],
            ],
            [
                'heading' => 'Changes to these terms',
                'body' => [
                    'If the application or the legal situation changes, these terms may be adjusted. Changes are announced in the application. If you do not agree, you can end the use and delete your account.',
                ],
            ],
            [
                'heading' => 'Final provisions',
                'body' => [
                    'German law applies. Mandatory consumer protection provisions remain unaffected. Should a provision be invalid, the remaining provisions stay in force.',
                ],
            ],
        ],
    ],

    /*
     | Texts that appear on all three pages: the head of the layout, the date of
     | the last change and the neutral wording for details that are not
     | configured yet.
     */
    'shared' => [
        'legal_heading' => 'Legal',
        'back_to_app' => 'Back to the application',
        'to_login' => 'Go to sign in',
        'updated' => 'Last updated: :date',
        'contact' => 'Questions about these pages? Write to :operator_email.',
        'placeholders' => [
            'operator' => 'the provider of this application',
            'operator_email' => 'the address given in the imprint',
            'hosting' => 'the hosting provider of this application',
            'mail_provider' => 'the mailing provider of this application',
            'location' => 'available on request from the provider',
            'date' => 'see configuration',
        ],
        'missing' => [
            'name' => 'Name of the provider',
            'street' => 'Street and number',
            'city' => 'Postcode and town',
            'email' => 'Email address for contact',
        ],
    ],
];
