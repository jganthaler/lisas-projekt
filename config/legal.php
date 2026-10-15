<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Angaben zum Anbieter (Impressum)
    |--------------------------------------------------------------------------
    |
    | Diese Werte erscheinen im Impressum und in der Datenschutzerklärung. Sie
    | gehören nicht in die Sprachdateien, weil sie nichts mit der Sprache zu
    | tun haben: Der Anbieter bleibt derselbe, egal ob die Seite deutsch,
    | italienisch oder englisch angezeigt wird.
    |
    | Solange die Pflichtangaben (Name, Straße, Ort, E-Mail) fehlen, zeigt das
    | Impressum einen Warnhinweis an (siehe App\Support\Legal). So kann die
    | Anwendung nicht versehentlich ohne Impressum veröffentlicht werden.
    |
    */

    'operator' => [
        'name' => env('LEGAL_OPERATOR_NAME'),
        'street' => env('LEGAL_OPERATOR_STREET'),
        'city' => env('LEGAL_OPERATOR_CITY'),
        'country' => env('LEGAL_OPERATOR_COUNTRY', 'Deutschland'),
        'email' => env('LEGAL_OPERATOR_EMAIL'),

        // Freiwillig: nach § 5 DDG genügt eine schnelle elektronische Kontakt-
        // aufnahme (also die E-Mail-Adresse), die Telefonnummer ist optional.
        'phone' => env('LEGAL_OPERATOR_PHONE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Hosting und Mail-Versand
    |--------------------------------------------------------------------------
    |
    | Wo die Daten liegen und über welchen Dienst die Bestätigungs- und
    | Passwort-Mails verschickt werden. Beides wird in der
    | Datenschutzerklärung genannt, weil es die Empfänger der Daten sind.
    |
    | Für beide Anbieter gilt: Es handelt sich um eine Verarbeitung im Auftrag,
    | die nach Art. 28 DSGVO vertraglich geregelt sein muss (Auftrags-
    | verarbeitungsvertrag, bei Hostern und Mail-Diensten meist Teil der
    | AGB bzw. des Vertrags). Fehlt die Angabe, steht in der Erklärung ein
    | neutraler Hinweis ("beim Anbieter zu erfragen").
    |
    */

    'hosting' => [
        'provider' => env('LEGAL_HOSTING_PROVIDER'),
        'location' => env('LEGAL_HOSTING_LOCATION'),
    ],

    'mail' => [
        'provider' => env('LEGAL_MAIL_PROVIDER'),
        'location' => env('LEGAL_MAIL_LOCATION'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Stand der Rechtstexte
    |--------------------------------------------------------------------------
    |
    | Datum der letzten Änderung (JJJJ-MM-TT), wie es unter jedem Rechtstext
    | steht. Ohne Angabe entfällt die Zeile.
    |
    */

    'updated_at' => env('LEGAL_UPDATED_AT'),

];
