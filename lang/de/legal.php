<?php

/*
 | Die Rechtsseiten der Anwendung: Impressum, Datenschutzerklärung und
 | Nutzungsbedingungen.
 |
 | Die Texte beschreiben, was diese Anwendung wirklich tut - welche Daten
 | gespeichert werden, wo sie liegen, welche Dienste beteiligt sind und welches
 | Cookie gesetzt wird. Die Angaben zum Anbieter (und zu Hosting und Mail)
 | stehen nicht hier, sondern in config/legal.php, das sie aus der Umgebung
 | liest (LEGAL_*); die Platzhalter ":operator", ":hosting", ":mail_provider"
 | und ":date" füllt App\Support\Legal beim Rendern.
 |
 | Jedes Dokument besteht aus "sections": Überschrift, Absätze und optional eine
 | Liste. So bleibt der Aufbau in allen drei Sprachen gleich
 | (resources/views/legal/*). Die deutschen Zeilen sind die Quelle der Wahrheit.
 |
 | Diese Texte sind Vorlagen: Sie decken den üblichen Fall einer privaten,
 | unentgeltlichen Haushalts-Anwendung ab und müssen vom Anbieter - im Zweifel
 | mit rechtlicher Beratung - geprüft werden (siehe README, Abschnitt
 | "Rechtliches").
 */

return [
    'imprint' => [
        'title' => 'Impressum',
        'heading' => 'Impressum',
        'intro' => 'Wer diese Anwendung bereitstellt und wie man den Anbieter erreicht.',
        'provider_heading' => 'Angaben gemäß § 5 DDG',
        'provider_note' => ':app ist ein privates, unentgeltliches Angebot zur gemeinsamen Verwaltung von Gefrierschränken im Haushalt. Es werden darüber keine Waren und keine Dienstleistungen verkauft.',
        'contact_heading' => 'Kontakt',
        'email_label' => 'E-Mail',
        'phone_label' => 'Telefon',
        'incomplete_heading' => 'Anbieterangaben fehlen',
        'incomplete_body' => 'Diese Seite ist noch unvollständig: In der Konfiguration sind keine Angaben zum Anbieter hinterlegt. Solange fehlt der Anwendung das notwendige Impressum. Auszufüllen sind die Werte LEGAL_* aus .env.example (siehe config/legal.php).',
        'missing_heading' => 'Es fehlen:',
        'sections' => [
            [
                'heading' => 'Haftung für Inhalte',
                'body' => [
                    'Die Inhalte dieser Anwendung entstehen aus den Eingaben ihrer Nutzerinnen und Nutzer: Namen, Mengen und Mindesthaltbarkeitsdaten von Lebensmitteln werden dort selbst gepflegt. Für die Richtigkeit, Vollständigkeit und Aktualität dieser Angaben wird keine Gewähr übernommen.',
                    'Als Diensteanbieter sind wir nicht verpflichtet, übermittelte oder gespeicherte fremde Informationen zu überwachen oder nach Umständen zu forschen, die auf eine rechtswidrige Tätigkeit hinweisen. Verpflichtungen zur Entfernung oder Sperrung von Informationen nach den allgemeinen Gesetzen bleiben davon unberührt.',
                ],
            ],
            [
                'heading' => 'Haftung für Links',
                'body' => [
                    'Diese Anwendung enthält keine Links zu Inhalten Dritter. Sollten Nutzerinnen oder Nutzer auf fremde Angebote verweisen, sind ausschließlich deren Betreiber dafür verantwortlich.',
                ],
            ],
            [
                'heading' => 'Verbraucherstreitbeilegung',
                'body' => [
                    'Wir sind nicht verpflichtet und nicht bereit, an einem Streitbeilegungsverfahren vor einer Verbraucherschlichtungsstelle teilzunehmen.',
                ],
            ],
            [
                'heading' => 'Anwendungsbereich',
                'body' => [
                    'Dieses Impressum gilt für :app unter :url.',
                ],
            ],
        ],
    ],

    'privacy' => [
        'title' => 'Datenschutz',
        'heading' => 'Datenschutzerklärung',
        'intro' => 'Welche Daten :app speichert, warum, wie lange – und welche Rechte du dabei hast.',
        'controller_heading' => 'Verantwortlich',
        'controller_intro' => 'Verantwortlich für die Verarbeitung personenbezogener Daten in dieser Anwendung ist:',
        'sections' => [
            [
                'heading' => 'Das Wichtigste in Kürze',
                'body' => [
                    ':app ist ein privates Angebot für den eigenen Haushalt. Die Anwendung verzichtet auf Werbung, Analyse-Werkzeuge und Profiling.',
                ],
                'list' => [
                    'Gespeichert werden nur die Daten, die für den Betrieb nötig sind: dein Konto, die Inhalte deiner Gefrierschränke und technische Sitzungsdaten.',
                    'Daten werden nicht verkauft und nicht zu Werbezwecken weitergegeben.',
                    'Neben dem technisch notwendigen Anmelde-Cookie wird im Browser nur deine Farbwahl (hell oder dunkel) gespeichert – deshalb gibt es hier auch kein Cookie-Banner.',
                    'Dein Konto kannst du jederzeit selbst im Profil löschen.',
                ],
            ],
            [
                'heading' => 'Konto und Anmeldung',
                'body' => [
                    'Zu jedem Konto speichern wir einen Anzeigenamen (freiwillig; ohne Angabe wird der Teil vor dem @ deiner E-Mail-Adresse angezeigt), deine E-Mail-Adresse, dein Passwort (ausschließlich als Hash, also nicht lesbar), die von dir gewählte Sprache, ein freiwillig hochgeladenes Profilbild und den Zeitpunkt der E-Mail-Bestätigung.',
                    'Diese Angaben brauchen wir, um dir ein Konto bereitzustellen, dich anzumelden und sicherzustellen, dass die E-Mail-Adresse wirklich dir gehört. Rechtsgrundlage ist Art. 6 Abs. 1 lit. b DSGVO.',
                ],
            ],
            [
                'heading' => 'Inhalte der Anwendung',
                'body' => [
                    'Gefrierschränke, Schubladen und Artikel (Name, Menge, Kategorie, Mindesthaltbarkeitsdatum) sowie Mitgliedschaften und Einladungen. Diese Angaben sind die eigentliche Funktion der Anwendung: Sie sehen alle, die Zugriff auf den jeweiligen Gefrierschrank haben – der Besitzer und die von ihm eingeladenen Personen.',
                    'Ein Einladungslink enthält ein zufälliges Token. Gespeichert wird davon nur ein Hash, der Link selbst nicht; er läuft nach sieben Tagen ab und kann einmal verwendet werden. Rechtsgrundlage ist Art. 6 Abs. 1 lit. b DSGVO.',
                ],
            ],
            [
                'heading' => 'Technische Daten',
                'body' => [
                    'Beim Anmelden legt die Anwendung eine Sitzung an. Gespeichert werden die Sitzungs-Kennung (als Cookie in deinem Browser), deine IP-Adresse, die Kennung deines Browsers (User-Agent), der Zeitpunkt der letzten Aktivität und – wenn du „Angemeldet bleiben“ wählst – ein dauerhaftes Anmelde-Token.',
                    'Diese Daten dienen dem Betrieb, der Sicherheit und der Abwehr von Missbrauch (Art. 6 Abs. 1 lit. f DSGVO). Fehlgeschlagene Anmeldeversuche werden kurzzeitig gezählt, damit Passwörter nicht durchprobiert werden können.',
                ],
            ],
            [
                'heading' => 'Protokolle des Hosting-Anbieters',
                'body' => [
                    'Der Hosting-Anbieter (:hosting) protokolliert den Abruf der Seiten in Server-Logfiles: IP-Adresse, Zeitpunkt, aufgerufene Adresse, Statuscode, übertragene Datenmenge und Browserkennung. Das ist für den technischen Betrieb, die Fehlersuche und die Abwehr von Angriffen erforderlich (Art. 6 Abs. 1 lit. f DSGVO).',
                    'Wie lange diese Protokolle aufbewahrt werden, richtet sich nach den Fristen des Anbieters (in der Regel wenige Tage); die Angaben dazu stehen in seiner Datenschutzerklärung.',
                ],
            ],
            [
                'heading' => 'E-Mails',
                'body' => [
                    'Die Anwendung verschickt nur Mails, die du selbst auslöst: die Bestätigung deiner E-Mail-Adresse nach der Registrierung und den Link zum Zurücksetzen deines Passworts. Dafür wird deine E-Mail-Adresse an den Versanddienst :mail_provider übergeben (Art. 6 Abs. 1 lit. b und lit. f DSGVO).',
                ],
            ],
            [
                'heading' => 'Rechtsgrundlagen',
                'body' => [
                    'Soweit oben nichts anderes genannt ist, verarbeiten wir Daten auf Grundlage von Art. 6 Abs. 1 lit. b DSGVO (Bereitstellung der Anwendung) und lit. f DSGVO (Sicherheit und Stabilität). Freiwillige Angaben wie der Anzeigename oder das Profilbild verarbeiten wir auf Grundlage deiner Einwilligung (Art. 6 Abs. 1 lit. a DSGVO); du kannst sie jederzeit ändern oder entfernen.',
                ],
                'note' => 'Eine erteilte Einwilligung kannst du jederzeit widerrufen (Art. 7 Abs. 3 DSGVO). Die Rechtmäßigkeit der bis dahin erfolgten Verarbeitung bleibt davon unberührt.',
            ],
            [
                'heading' => 'Cookies und lokale Speicherung',
                'body' => [
                    'Diese Anwendung setzt keine Cookies zu Werbe- oder Analysezwecken. Ein Einwilligungsbanner ist deshalb nicht erforderlich.',
                ],
                'list' => [
                    'laravel_session – technisch notwendiges Cookie für die Anmeldung. Es wird beim Abmelden gelöscht und läuft nach 120 Minuten ohne Aktivität ab (§ 25 Abs. 2 Nr. 2 TDDDG).',
                    'Ein Anmelde-Cookie („Angemeldet bleiben“) – nur, wenn du diese Auswahl im Anmeldeformular triffst. Es hält dich nach dem Schließen des Browsers angemeldet.',
                    'color-scheme im lokalen Speicher des Browsers – merkt sich, ob du die Anwendung hell oder dunkel benutzt. Der Eintrag enthält keine Kennung und wird nicht ausgewertet.',
                ],
                'note' => 'Diese Einträge kannst du im Browser jederzeit selbst löschen; danach musst du dich nur neu anmelden.',
            ],
            [
                'heading' => 'Empfänger',
                'body' => [
                    'Deine Daten bleiben in dieser Anwendung. Sie werden nicht verkauft und nicht zu Werbezwecken weitergegeben. Zugriff auf die Daten haben nur:',
                ],
                'list' => [
                    'Hosting: :hosting (Standort: :hosting_location). Der Anbieter verarbeitet Datenbank, Dateien und Protokolle in unserem Auftrag nach Art. 28 DSGVO.',
                    'E-Mail-Versand: :mail_provider (Standort: :mail_location) – ausschließlich für die Bestätigungs- und Passwort-Mails.',
                    'Personen mit Zugriff auf einen Gefrierschrank: Sie sehen die Inhalte dieses Gefrierschranks, weil genau das die Funktion der Anwendung ist.',
                ],
                'note' => 'Eine Übermittlung in Länder außerhalb der Europäischen Union findet nicht statt, wenn die genannten Anbieter ihren Sitz in der EU haben. Andernfalls stützt sie sich auf die Standardvertragsklauseln der EU-Kommission.',
            ],
            [
                'heading' => 'Speicherdauer',
                'body' => [
                    'Daten werden so lange gespeichert, wie es für den jeweiligen Zweck nötig ist:',
                ],
                'list' => [
                    'Konto (Anzeigename, E-Mail-Adresse, Passwort-Hash, Sprache): bis du dein Konto löschst.',
                    'Profilbild: bis du es entfernst oder dein Konto löschst.',
                    'Gefrierschränke, Schubladen und Artikel: bis sie gelöscht werden oder das Konto gelöscht wird.',
                    'Sitzungen (IP-Adresse, Browserkennung): bis zum Abmelden, spätestens nach 120 Minuten ohne Aktivität.',
                    'Einladungslinks: sieben Tage.',
                    'Links zur E-Mail-Bestätigung und zum Passwort-Zurücksetzen: 60 Minuten.',
                    'Einträge zum Passwort-Zurücksetzen: bis der Link benutzt oder ein neuer angefordert wird.',
                    'Server-Protokolle des Hosting-Anbieters: nach dessen Fristen.',
                ],
            ],
            [
                'heading' => 'Deine Rechte',
                'body' => [
                    'Dir stehen gegenüber dem Verantwortlichen diese Rechte zu:',
                ],
                'list' => [
                    'Auskunft über die verarbeiteten Daten (Art. 15 DSGVO)',
                    'Berichtigung unrichtiger Daten (Art. 16 DSGVO)',
                    'Löschung (Art. 17 DSGVO)',
                    'Einschränkung der Verarbeitung (Art. 18 DSGVO)',
                    'Datenübertragbarkeit (Art. 20 DSGVO)',
                    'Widerspruch gegen Verarbeitungen auf Grundlage berechtigter Interessen (Art. 21 DSGVO)',
                ],
                'note' => 'Für Auskunft, Berichtigung oder Übertragbarkeit genügt eine Nachricht an :operator_email. Außerdem kannst du dich bei einer Aufsichtsbehörde beschweren (Art. 77 DSGVO) – zum Beispiel bei der Behörde an deinem Wohnort.',
            ],
            [
                'heading' => 'Konto löschen',
                'body' => [
                    'Im Profil findest du den Knopf „Konto löschen“. Zur Sicherheit musst du dafür dein Passwort eingeben. Gelöscht werden das Konto, das Profilbild, die Anmelde-Sitzungen und alle von dir angelegten Gefrierschränke samt Schubladen und Artikeln.',
                    'Gefrierschränke, die andere mit dir geteilt haben, bleiben bestehen – dort verlierst du nur den Zugriff. Eine Löschung lässt sich nicht rückgängig machen. Alternativ genügt eine Nachricht an :operator_email.',
                ],
            ],
            [
                'heading' => 'Sicherheit',
                'body' => [
                    'Die Verbindung zur Anwendung ist verschlüsselt (HTTPS). Passwörter werden ausschließlich als Hash gespeichert und sind auch für den Anbieter nicht lesbar. Profilbilder liegen in einem nicht öffentlich erreichbaren Verzeichnis und werden nur an angemeldete Personen ausgeliefert.',
                ],
            ],
            [
                'heading' => 'Keine automatisierte Entscheidungsfindung',
                'body' => [
                    'Es findet kein Profiling und keine automatisierte Entscheidungsfindung im Sinne des Art. 22 DSGVO statt. Die MHD-Ampel der Anwendung ist eine einfache Berechnung aus dem von dir gespeicherten Datum.',
                ],
            ],
            [
                'heading' => 'Änderungen dieser Erklärung',
                'body' => [
                    'Wenn sich die Anwendung ändert, wird diese Erklärung angepasst. Es gilt jeweils die hier veröffentlichte Fassung; das Datum der letzten Änderung steht unten auf der Seite.',
                ],
            ],
        ],
    ],

    'terms' => [
        'title' => 'Nutzungsbedingungen',
        'heading' => 'Nutzungsbedingungen',
        'intro' => 'Die Regeln für die Nutzung von :app.',
        'sections' => [
            [
                'heading' => 'Geltung',
                'body' => [
                    'Diese Nutzungsbedingungen gelten für die Nutzung von :app. Anbieter ist die im Impressum genannte Person. Mit der Registrierung erkennst du diese Bedingungen an.',
                ],
            ],
            [
                'heading' => 'Was die Anwendung leistet',
                'body' => [
                    'Die Anwendung hilft dabei, den Inhalt von Gefrierschränken gemeinsam zu verwalten und an Mindesthaltbarkeitsdaten zu erinnern. Sie ist ein Hilfsmittel und keine Zusicherung zur Qualität oder Genießbarkeit von Lebensmitteln: Namen, Mengen und Mindesthaltbarkeitsdaten stammen von den Nutzerinnen und Nutzern, und ob ein Lebensmittel noch verzehrt wird, entscheidet jede Person selbst.',
                ],
            ],
            [
                'heading' => 'Konto',
                'body' => [
                    'Für die Nutzung brauchst du ein Konto. Dabei gilt:',
                ],
                'list' => [
                    'Du gibst eine E-Mail-Adresse an, die dir gehört, und bestätigst sie über den Link in der Bestätigungsmail.',
                    'Du hältst dein Passwort geheim und informierst den Anbieter, wenn du den Verdacht hast, dass jemand anderes Zugriff auf dein Konto hat.',
                    'Ein Konto pro Person; eine Weitergabe des Kontos an andere ist nicht erlaubt.',
                    'Du musst mindestens 16 Jahre alt sein. Jüngere Personen dürfen die Anwendung nur mit Zustimmung der erziehungsberechtigten Person nutzen.',
                    'Deine Angaben sind richtig und aktuell zu halten.',
                ],
            ],
            [
                'heading' => 'Regeln für die Nutzung',
                'body' => [
                    'Beim Umgang mit der Anwendung gilt:',
                ],
                'list' => [
                    'Keine rechtswidrigen, beleidigenden oder herabwürdigenden Inhalte.',
                    'Keine Versuche, die Anwendung zu stören, zu überlasten, automatisiert auszulesen oder Zugriffsrechte zu umgehen.',
                    'Einladungslinks sind persönlich: Wer den Link hat, erhält Zugriff auf den Gefrierschrank. Gib ihn deshalb nur an Personen weiter, die du wirklich einladen möchtest.',
                    'Kein Missbrauch fremder Konten und kein Hochladen von Inhalten, an denen du keine Rechte hast.',
                ],
            ],
            [
                'heading' => 'Verfügbarkeit',
                'body' => [
                    'Die Anwendung wird unentgeltlich bereitgestellt. Einen Anspruch auf ständige Verfügbarkeit gibt es nicht; Wartung, Änderungen und auch die Einstellung des Angebots sind möglich. Notiere dir wichtige Angaben zusätzlich, wenn du sie länger brauchst.',
                ],
            ],
            [
                'heading' => 'Haftung',
                'body' => [
                    'Der Anbieter haftet unbeschränkt bei Vorsatz und grober Fahrlässigkeit, bei Verletzung von Leben, Körper oder Gesundheit sowie nach dem Produkthaftungsgesetz. Bei einfacher Fahrlässigkeit haftet er nur für die Verletzung wesentlicher Vertragspflichten und der Höhe nach begrenzt auf den vorhersehbaren, vertragstypischen Schaden. Im Übrigen ist die Haftung ausgeschlossen – insbesondere für Schäden aus Entscheidungen, die du auf Grundlage der in der Anwendung gespeicherten Angaben triffst, und für Datenverlust, soweit der Schaden durch eine zumutbare Datensicherung vermeidbar gewesen wäre.',
                ],
            ],
            [
                'heading' => 'Laufzeit und Beendigung',
                'body' => [
                    'Du kannst die Nutzung jederzeit beenden, indem du dein Konto im Profil löschst. Der Anbieter kann ein Konto sperren oder löschen, wenn gegen diese Bedingungen verstoßen wird oder die Sicherheit der Anwendung es erfordert. Gelöschte Daten können nicht wiederhergestellt werden.',
                ],
            ],
            [
                'heading' => 'Änderungen dieser Bedingungen',
                'body' => [
                    'Wenn sich die Anwendung oder die Rechtslage ändert, können diese Bedingungen angepasst werden. Änderungen werden in der Anwendung angekündigt. Wenn du nicht einverstanden bist, kannst du die Nutzung beenden und dein Konto löschen.',
                ],
            ],
            [
                'heading' => 'Schlussbestimmungen',
                'body' => [
                    'Es gilt deutsches Recht. Zwingende Verbraucherschutzvorschriften bleiben unberührt. Sollte eine Bestimmung unwirksam sein, bleibt die übrige Regelung wirksam.',
                ],
            ],
        ],
    ],

    /*
     | Texte, die auf allen drei Seiten vorkommen: der Kopf des Layouts, das
     | Datum der letzten Änderung und die neutralen Ersatztexte für Angaben, die
     | noch nicht in der Konfiguration stehen.
     */
    'shared' => [
        'legal_heading' => 'Rechtliches',
        'back_to_app' => 'Zurück zur Anwendung',
        'to_login' => 'Zur Anmeldung',
        'updated' => 'Stand: :date',
        'contact' => 'Fragen zu diesen Seiten? Schreib an :operator_email.',
        'placeholders' => [
            'operator' => 'der Anbieter dieser Anwendung',
            'operator_email' => 'die im Impressum genannte Adresse',
            'hosting' => 'der Hosting-Anbieter dieser Anwendung',
            'mail_provider' => 'der Mail-Anbieter dieser Anwendung',
            'location' => 'beim Anbieter zu erfragen',
            'date' => 'siehe Konfiguration',
        ],
        'missing' => [
            'name' => 'Name des Anbieters',
            'street' => 'Straße und Hausnummer',
            'city' => 'Postleitzahl und Ort',
            'email' => 'E-Mail-Adresse für Kontakt',
        ],
    ],
];
