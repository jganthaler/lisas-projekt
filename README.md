# Coolventory

Geteilte Gefrierschrank-Verwaltung für den Haushalt: Wer hat was eingefroren und
wann läuft das MHD ab? Coolventory beantwortet diese Fragen für alle, die sich
einen Gefrierschrank teilen – inklusive Einladungen, Rollen und MHD-Ampel.

## Funktionen

- **Gefrierschränke mit Schubladen** – jeder Gefrierschrank hat Schubladen, in
  denen die Artikel liegen. Beim Öffnen sieht man sofort, welche Artikel bald
  ablaufen.
- **MHD-Ampel** – Artikel sind automatisch *abgelaufen*, *bald fällig* (≤ 7 Tage),
  *frisch* oder *ohne MHD*. Die Seite „MHD-Warnungen“ (unten rechts in der
  Navigation) sammelt alle kritischen Artikel über alle Gefrierschränke hinweg –
  unabhängig davon, welcher Gefrierschrank gerade aktiv ist; jede Zeile nennt
  dazu Gefrierschrank und Schublade. Der MHD-Zähler der App-Hülle – die runde
  Zahl am MHD-Feld unten in der Navigation – zählt über alle zugänglichen
  Gefrierschränke hinweg, also dieselbe Menge wie die Liste. Artikel ohne Datum
  zeigen in den Listen gar keine Angabe – statt „ohne MHD“ bleibt die Zeile
  leer.
- **Artikelverwaltung** – Menge erhöhen/verringern, umbenennen, löschen und
  suchen. Vor dem Löschen fragt die Zeile selbst nach („Möchtest du … wirklich
  löschen?“) – die Rückfrage des Browsers kommt dabei nicht mehr zum Einsatz.
  Sortiert wird über den vorberechneten Sortierschlüssel `name_sort`,
  damit „Äpfel“ auch in MySQL an der richtigen Stelle steht. Den Schlüssel
  füllt das Model beim Speichern, die Spalte ist `NOT NULL`.
- **Teilen per Einladungslink** – der Besitzer erzeugt einen Link. In der
  Datenbank liegt nur der SHA-256-Hash des Tokens (`Invitation::hashToken()`);
  der Klartext-Link wird genau einmal angezeigt. Nach dem Login landet man
  direkt in der angenommenen Einladung.
- **Rollen** – Besitzer dürfen den Gefrierschrank umbenennen, Mitglieder
  entfernen und den Gefrierschrank löschen. Mitglieder dürfen Artikel verwalten,
  den Gefrierschrank wechseln und ihn verlassen.
- **Mehrere Gefrierschränke** – auf der Seite „Gefrierschrank“ legt das Plus
  neben „Meine Gefrierschränke“ einen weiteren Gefrierschrank an (mit drei
  Schubladen), und zwischen allen lässt sich dort wechseln; die eigenen
  Gefrierschränke können umbenannt werden. Der aktive Gefrierschrank liegt in
  der Session (`App\Support\FreezerContext`); der eigene Gefrierschrank
  gewinnt, solange nicht explizit gewechselt wurde.
- **Inventar über alle Gefrierschränke** – die Seite „Inventar“ listet die
  Artikel aller zugänglichen Gefrierschränke – eigene und geteilte – in einer
  Liste. Das Filter-Icon neben dem Suchfeld öffnet die Auswahl nach
  Gefrierschrank; ein Punkt am Icon zeigt an, dass ein Filter
  greift. Bei mehreren Gefrierschränken nennt jede Zeile zusätzlich, woher der
  Artikel kommt.
- **E-Mail-Verifizierung** – ein neues Konto muss seine E-Mail-Adresse
  bestätigen: Die Registrierung verschickt einen signierten Link, bis zur
  Bestätigung sperrt die `verified`-Middleware die App, und die Hinweisseite
  verschickt auf Wunsch einen neuen Link. Der Link hängt an Konto (`id`) und
  Adresse (`hash`), läuft nach einer Stunde ab und funktioniert nur für den
  angemeldeten Nutzer. Die Mail ist in der Sprache des Kontos
  (`VerifyEmail::toMailUsing()` im `AppServiceProvider`).
- **Sprachen** – die App spricht Deutsch, Italienisch und Englisch. Die Wahl
  steht im Profil direkt unter dem Profilbild als „Deutsch“, „Italiano“ und
  „English“ – jede Sprache immer
  in ihrer eigenen Schreibweise, damit sie in jeder Oberfläche erkennbar bleibt.
  Gespeichert wird die Wahl am Konto (`users.locale`), sodass jede weitere
  Anfrage sie übernimmt (`App\Http\Middleware\SetLocale`); die Antwort auf den
  Klick kommt bereits in der neuen Sprache. Ohne Wahl bleibt es bei Deutsch,
  Gäste und brandneue Konten sehen also weiterhin Deutsch. Auch die Mails
  richten sich nach dem Konto (`HasLocalePreference`), und ein neuer
  Gefrierschrank samt Schubladen bekommt seinen Namen in der Sprache des
  Augenblicks. Alle Texte liegen in `lang/de`, `lang/it` und `lang/en`; die
  deutschen Dateien sind die Quelle der Wahrheit, weil die Tests ihre Wortwahl
  prüfen.
- **Profil** – Profilbild, Sprache der App, Name und E-Mail, Passwortwechsel,
  Rechtliches, das Löschen des Kontos und ganz unten das Abmelden, in dieser
  Reihenfolge; das Bild wird beim Auswählen sofort gespeichert (`updatedAvatar()`
  in der Komponente) – ein neues Bild ersetzt das alte samt Datei, ein Knopf zum
  Speichern gibt es nicht. Ausgeliefert wird der Avatar über `/avatars/{user}`
  aus dem privaten `local`-Disk; die URL trägt eine Version des gespeicherten
  Pfades, damit der Browser nach dem Austausch nicht das alte Bild zeigt. Ein
  zweiter Knopf löscht das Konto samt Profilbild, Sitzungen und eigenen
  Gefrierschränken (Art. 17 DSGVO) – abgesichert durch die Eingabe des
  Passworts und die Rückfrage der App.
- **Rechtliches** – Impressum, Datenschutzerklärung und Nutzungsbedingungen
  liegen unter `/impressum`, `/datenschutz` und `/nutzungsbedingungen` und sind
  ohne Anmeldung lesbar (`App\Http\Controllers\LegalController`). Die Texte
  stehen in `lang/<sprache>/legal.php`, die Angaben zum Anbieter kommen aus der
  Umgebung (`LEGAL_*`, siehe „Rechtliches“ weiter unten).
- **Oberfläche** – Buttons, Felder, Auswahlfelder und Kacheln teilen sich einen
  Eckenradius von 8 px (`--radius-control` in `@theme`, in den Views als
  `rounded-control`). Ein schmaler Ladebalken am oberen Rand zeigt laufende
  Livewire-Requests; Seitenwechsel über `wire:navigate` nutzen die Leiste von
  Livewire in derselben Farbe.
- **Farbschema** – Grau von Hellgrau bis Anthrazit trägt alles außer der
  Akzentfarbe: `frost` (in `resources/css/app.css`) liefert Seitenhintergrund,
  Karten, Linien und Texte, `brand` ist das ruhige Türkis für Aktionen und
  Auswahl. Beide Modi kommen aus derselben Rampe – im Tagmodus die dunklen
  Stufen (`brand-600` für Flächen mit weißer Schrift, `brand-700` für Text), im
  Nachtmodus die hellen (`brand-300` auf `brand-950`); der `.dark`-Block dreht
  nur die Graustufen um, damit `frost-100` in beiden Modi der Seitenhintergrund
  bleibt – auch auf den Anmeldeseiten, wo die Wortmarke nur auf dem hellen Grau
  ihre eigenen Farben behalten kann (im Nachtmodus wird sie invertiert).
  Abgelaufen und bald fällig haben eigene, gedämpfte Rampen (`danger`,
  `warning`) statt der Tailwind-Farben, und der Dialog-Hintergrund liegt auf
  `scrim`, damit er in beiden Modi abdunkelt statt im Nachtmodus aufzuhellen.
  Jedes Paar aus Tönung und Beschriftung erreicht mindestens 4,5:1.

## Technik

| Baustein | Version |
| --- | --- |
| PHP | 8.5 (Sail-Image `sail-8.5/app`) |
| Laravel | 13.35 |
| Livewire | 4.4 (die gesamte Oberfläche, kein JS-Framework) |
| Tailwind CSS | 4.x über `@tailwindcss/vite` |
| Vite | 8.x |
| MySQL | 8.4 |
| Tests | PHPUnit 12.5 – 174 Tests / 714 Assertions |

Die Oberfläche besteht vollständig aus Livewire-Komponenten
(`app/Livewire/**`); die einzigen klassischen Routen sind der Logout, die
Avatar-Auslieferung (nur für angemeldete Nutzer), die drei Rechtsseiten und der
signierte Bestätigungslink (`VerifyEmailController`).

## Loslegen (Windows + Docker Desktop)

```powershell
# 1. Abhängigkeiten
composer install

# 2. Container starten (App auf http://localhost:8000, MySQL auf 3306)
docker compose up -d

# 3. Anwendungsschlüssel und Datenbank
docker compose exec --user sail laravel.test php artisan key:generate
docker compose exec --user sail laravel.test php artisan migrate --seed

# 4. Frontend-Assets bauen (oder npm run dev, siehe unten)
docker compose exec --user sail laravel.test npm install
docker compose exec --user sail laravel.test npm run build
```

> **Wichtig:** Immer `--user sail` mitgeben (genau das macht auch das
> `sail`-Skript). Ohne die Angabe läuft der Befehl als `root` und legt
> root-eigene Dateien in `storage/` an, die Laravel im Web-Request nicht mehr
> schreiben kann – siehe „Hinweise“.

Danach ist die App unter <http://localhost:8000> erreichbar.

### Demo-Zugänge (nach `migrate --seed`)

| E-Mail | Passwort | Rolle |
| --- | --- | --- |
| `lisa@example.com` | `password` | Besitzerin von „Gefrierschrank Küche“, Mitglied in „Truhe im Keller“ |
| `jonas@example.com` | `password` | Besitzer von „Truhe im Keller“ |

Der Seeder legt zusätzlich Artikel in allen MHD-Zuständen sowie eine offene
Einladung für „Gefrierschrank Küche“ an. Die Demo-Konten gelten als bestätigt
(`email_verified_at`), damit man direkt loslegen kann.

### Frontend während der Entwicklung

```powershell
docker compose exec --user sail laravel.test npm run dev   # Vite-Dev-Server mit HMR (Port 5173)
```

Solange der Dev-Server läuft, schreibt Vite `public/hot` und `@vite(...)` lädt
die Assets aus dem Dev-Server; andernfalls wird das gebaute Manifest aus
`public/build` verwendet.

## Tests

```powershell
docker compose exec --user sail laravel.test php artisan test
```

Der Testbefehl nutzt die Datenbank `testing` (wird vom MySQL-Container
automatisch angelegt) und deckt die fachlichen Zusagen der App ab:

| Datei | Inhalt |
| --- | --- |
| `tests/Unit/InventoryItemStateTest.php` | MHD-Zustände und Sortierschlüssel |
| `tests/Feature/AuthenticationTest.php` | Login, Registrierung, Logout |
| `tests/Feature/EmailVerificationTest.php` | Bestätigungslink, Hinweisseite, erneutes Senden |
| `tests/Feature/PageRenderTest.php` | Alle Seiten rendern für angemeldete Nutzer |
| `tests/Feature/FreezerAccessTest.php` | Fremde Gefrierschränke/Schubladen/Artikel bleiben unerreichbar |
| `tests/Feature/DrawerPageTest.php` | Artikel einer Schublade anlegen, ändern, löschen (Rückfrage in der Zeile) |
| `tests/Feature/InventoryOverviewTest.php` | Übersicht über alle Gefrierschränke, Suche, Filter-Panel, Sortierung, Löschen mit Rückfrage |
| `tests/Feature/ExpiringItemsTest.php` | Seite „MHD-Warnungen“ über alle Gefrierschränke, Zähler in der Fußleiste |
| `tests/Feature/SharingPageTest.php` | Einladen, Umbenennen, Mitglieder entfernen, Verlassen, Löschen |
| `tests/Feature/ClosedFreezerTest.php` | Gefrierschränke anlegen, wechseln, umbenennen |
| `tests/Feature/InvitationTest.php` | Einladungslink annehmen, Ablauf, Mehrfachnutzung |
| `tests/Feature/ProfileTest.php` | Profildaten, Passwort, Profilbild (speichert beim Auswählen, ersetzt das alte), Konto löschen |
| `tests/Feature/LanguageTest.php` | Sprachwahl im Profil, Sprache aller Seiten und Mails |
| `tests/Feature/LegalPagesTest.php` | Impressum, Datenschutzerklärung, Nutzungsbedingungen: ohne Anmeldung lesbar, Anbieterangaben, Platzhalter, Verlinkung |

## Aufbau

```
app/
  Livewire/          Oberfläche: Auth (inkl. E-Mail-Hinweis), Freezer, Invitations,
                     Profile, Sharing, InventoryOverview, ExpiringItems,
                     Concerns\InteractsWithItems
  Http/Controllers/  Logout, Avatar-Auslieferung (nur angemeldet), LegalController
                     (Impressum, Datenschutz, Nutzungsbedingungen),
                     VerifyEmailController (signierter Link)
  Http/Middleware/   SetLocale (Sprache der Anfrage aus dem Konto)
  Models/            User, Freezer, FreezerMembership, Drawer, InventoryItem, Invitation
  Policies/          FreezerPolicy, DrawerPolicy, InventoryItemPolicy
  Support/           FreezerContext (aktiver Gefrierschrank in der Session),
                     Locales (die Sprachen der App),
                     Legal (Anbieterangaben und Platzhalter der Rechtstexte)
lang/de, lang/it, lang/en    Alle Texte, ein Verzeichnis pro Sprache
resources/views/livewire/**   Blade-Templates der Komponenten
resources/views/components/** Wiederverwendbare UI-Bausteine (card, notice, avatar, …)
resources/views/legal/**      Impressum, Datenschutzerklärung, Nutzungsbedingungen
                              (eigenes Layout, weil sie ohne Konto lesbar sind)
database/factories, database/seeders
```

## Rechtliches

Die App bringt drei Seiten mit, die ohne Anmeldung erreichbar sind:

| Adresse | Inhalt |
| --- | --- |
| `/impressum` | Anbieter, Kontakt, Haftung, Verbraucherstreitbeilegung |
| `/datenschutz` | Verantwortlicher, Daten, Rechtsgrundlagen, Cookies, Empfänger, Speicherdauer, Rechte, Konto löschen |
| `/nutzungsbedingungen` | Was die App leistet, Konto, Regeln, Verfügbarkeit, Haftung, Beendigung |

Die Texte liegen in `lang/de|it|en/legal.php` (deutsch ist die Quelle der
Wahrheit) und sind in Abschnitte gegliedert; gerendert werden sie von
`resources/views/legal/*` mit `resources/views/layouts/legal.blade.php`.

**Was der Betreiber noch tun muss** (die Seiten sind Vorlagen, keine
Rechtsberatung – im Zweifel prüfen lassen):

1. **Anbieterangaben hinterlegen.** `LEGAL_OPERATOR_NAME`, `_STREET`, `_CITY`,
   `_COUNTRY`, `_EMAIL` und optional `_PHONE` in der `.env` (siehe
   `.env.example`, `config/legal.php`). Solange Name, Anschrift oder E-Mail
   fehlen, zeigt das Impressum einen **Warnhinweis** – die App soll nicht
   versehentlich ohne Impressum online gehen. Nach § 5 DDG genügt die
   E-Mail-Adresse als schnelle elektronische Kontaktaufnahme; eine reine
   Privatseite ohne geschäftsmäßiges Angebot braucht streng genommen kein
   Impressum, ein öffentlich erreichbarer Dienst sollte aber eines haben.
2. **Hoster und Mail-Dienst eintragen** (`LEGAL_HOSTING_*`, `LEGAL_MAIL_*`).
   Beide sind Empfänger im Sinne der Datenschutzerklärung, und für beide
   braucht es eine Verarbeitung im Auftrag nach Art. 28 DSGVO
   (Auftragsverarbeitungsvertrag; bei Hostern und Mail-Diensten meist Teil der
   AGB). Fehlt die Angabe, steht in der Erklärung ein neutraler Text
   („beim Anbieter zu erfragen“) statt einer Lücke.
3. **Stand-Datum setzen** (`LEGAL_UPDATED_AT`, `JJJJ-MM-TT`) – es erscheint
   unter jeder der drei Seiten.
4. **Prüfen, ob die Texte zum eigenen Fall passen:** Verkauf oder
   Werbung (dann Impressumspflicht und ggf. weitere Pflichtangaben), ein
   kommerzieller Betrieb, ein anderer Hoster, weitere Dienste (Analytics,
   Karten, Schriften von fremden Servern), Minderjährige als Nutzer.

**Was die App dafür schon tut** (damit die Texte stimmen):

- Kein Tracking, keine Werbung, keine Analyse-Werkzeuge, keine Inhalte von
  fremden Servern (Schriften liegen im eigenen Build).
- Nur ein technisch notwendiges Cookie (`laravel_session`) und der Eintrag
  `color-scheme` im lokalen Speicher des Browsers – deshalb ist **kein
  Cookie-Banner** nötig (§ 25 Abs. 2 Nr. 2 TDDDG).
- Passwörter nur als Hash, Profilbilder auf dem privaten `local`-Disk und nur
  über `/avatars/{user}` für angemeldete Nutzer abrufbar.
- Einladungen speichern nur den Hash des Tokens; Mails gehen ausschließlich an
  das Konto selbst (Bestätigung, Passwort-Reset).
- Konto löschen im Profil (Art. 17 DSGVO): Entfernt das Konto, das Profilbild,
  die Sitzungen, offene Passwort-Links und die eigenen Gefrierschränke samt
  Inhalt. Geteilte Gefrierschränke anderer bleiben bestehen, dort verschwindet
  nur die Mitgliedschaft.
- Der Hinweis auf Nutzungsbedingungen und Datenschutzerklärung steht im
  Registrierungsformular (Einbeziehung der Bedingungen, Art. 13 DSGVO).

**Nicht abgedeckt** (bewusst offen gelassen): Auskunfts- und
Übertragbarkeitsersuchen (Art. 15, 20 DSGVO) beantwortet der Betreiber per
E-Mail; ein Datenexport in der App gibt es nicht. Ebenso fehlen eigene
Fehlerseiten (403/404/419 kommen von Laravel auf Englisch).

## Hinweise

- **Datenbank nach dem Update:** Nach einem `git pull` können Migrationen offen
  sein. Ohne sie bricht zum Beispiel die Sprachwahl mit
  `Unknown column 'locale' in 'field list'` ab. Dann einmal
  `docker compose exec --user sail laravel.test php artisan migrate` ausführen –
  die Tests fallen darauf nicht herein, weil sie ihre Datenbank frisch aufbauen.
- **Sprachen:** Neue Texte gehören in alle drei Verzeichnisse unter `lang/`
  (`de`, `it`, `en`); die deutschen Zeilen sind die Quelle der Wahrheit, weil die
  Tests ihre Wortwahl prüfen. Ein Konto ohne gültige Sprache fällt auf
  `config('app.locale')` zurück (Deutsch) – so bleibt die App auch ohne
  Datenmigration für Bestandskonten benutzbar.
- **Rechte im Container:** Die Container-Prozesse laufen als `sail`
  (uid 1000). Beim Ausführen von Befehlen als root entstehen root-eigene
  Dateien in `storage/` und `bootstrap/cache`, die Laravel im Web-Request nicht
  mehr schreiben kann („Vite manifest not found“ bzw. `tempnam()`-Warnung).
  Deshalb bevorzugt
  `docker compose exec --user sail laravel.test <befehl>` verwenden und im
  Fehlerfall einmalig
  `docker compose exec --user root laravel.test chown -R sail:sail storage bootstrap/cache`
  ausführen.
- **Tokens:** Einladungslinks enthalten das Token nur im Klartext; gespeichert
  wird ausschließlich der Hash. Ein Link ist bis zum Ablauf
  (`Invitation::VALID_DAYS`) und nur einmal einlösbar.
- **Passwörter:** Der Demo-Seeder verwendet bewusst `password` – nicht für
  produktive Installationen gedacht.
- **Mails:** In der Entwicklung schreibt `MAIL_MAILER=log` jede Mail nach
  `storage/logs/laravel.log` – der Bestätigungslink steht dort zum Anklicken
  bereit. Für produktive Installationen einen echten Mailer konfigurieren.
- **Bestandsdaten:** Konten aus der Zeit vor der E-Mail-Verifizierung haben kein
  `email_verified_at` und landen auf der Hinweisseite. Für die Demo-Daten löst
  `php artisan migrate:fresh --seed` das Problem; einzelne Konten markiert man
  einmalig per Tinker mit
  `App\Models\User::whereNull('email_verified_at')->update(['email_verified_at' => now()]);`.
- **Code-Stil:** `docker compose exec --user sail laravel.test ./vendor/bin/pint`
  formatiert nach dem Laravel-Preset (`.gitattributes` erzwingt LF-Zeilenenden).
- **Schriften:** Der Vite-Build lädt „Instrument Sans“ über `laravel-vite-plugin/fonts`
  (Bunny) und legt die Dateien unter `public/build/assets` ab. Die Meldung
  *„Optimized font fallbacks require the optional fontaine package“* ist nur ein
  Hinweis und kann ignoriert werden.
