<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kontaktangabe (Impressum)
    |--------------------------------------------------------------------------
    |
    | Diese Angabe erscheint im Impressum und in der Datenschutzerklärung. Sie
    | gehört nicht in die Sprachdateien, weil sie nichts mit der Sprache zu
    | tun hat: Der Kontakt bleibt derselbe, egal ob die Seite deutsch,
    | italienisch oder englisch angezeigt wird.
    |
    | Der Anbieter sitzt in Italien (Südtirol). Die Rechtstexte in
    | lang/<sprache>/legal.php richten sich deshalb nach italienischem Recht:
    | D.Lgs. 70/2003 für den Dienst, D.Lgs. 196/2003 (Codice Privacy) und die
    | DSGVO für die Daten, Codice del Consumo für die Nutzungsbedingungen.
    |
    | Name und Anschrift stehen hier bewusst nicht: Für ein privates, nicht
    | kommerzielles Angebot verlangt italienisches Recht keine Anbieterangaben
    | (Art. 7 D.Lgs. 70/2003 und Art. 49 Codice del Consumo treffen gewerbliche
    | Dienste). Die E-Mail-Adresse genügt als schnelle elektronische
    | Kontaktaufnahme und ist damit die einzige Angabe, die im Impressum steht.
    | Solange sie fehlt, zeigt das Impressum einen Warnhinweis an (siehe
    | App\Support\Legal). So kann die Anwendung nicht versehentlich ohne
    | Kontaktmöglichkeit veröffentlicht werden.
    |
    */

    'operator' => [
        'email' => env('LEGAL_OPERATOR_EMAIL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Öffentliche Adresse des Angebots
    |--------------------------------------------------------------------------
    |
    | Die Adresse, unter der das Angebot im Internet erreichbar ist. Sie steht im
    | Geltungsbereich des Impressums ("Dieses Impressum gilt für Coolventory
    | unter ..."). Sie ist bewusst getrennt von APP_URL: APP_URL ist die Adresse
    | der laufenden Installation - lokal http://localhost:8000 -, die
    | Rechtstexte müssen aber die Adresse des veröffentlichten Angebots nennen,
    | sonst stünde im Impressum "localhost". Fehlt die Angabe, setzt der Text
    | einen neutralen Satz ein (siehe App\Support\Legal).
    |
    */

    'site' => env('LEGAL_SITE_URL', 'www.coolventory.com'),

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
