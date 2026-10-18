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
  suchen. Vor dem Löschen fragt das Modal der App nach („Möchtest du … wirklich
  löschen?“) – die Rückfrage des Browsers kommt dabei nicht mehr zum Einsatz.
  Sortiert wird über den vorberechneten Sortierschlüssel `name_sort`,
  damit „Äpfel“ auch in MySQL an der richtigen Stelle steht. Den Schlüssel
  füllt das Model beim Speichern, die Spalte ist `NOT NULL`.
- **Teilen per Einladungslink** – der Besitzer erzeugt einen Link. In der
  Datenbank liegt nur der SHA-256-Hash des Tokens (`Invitation::hashToken()`);
  der Klartext-Link wird genau einmal angezeigt. Nach dem Login landet man
  direkt in der angenommenen Einladung. Ein Link lädt genau eine Person ein:
  Er verfällt, sobald jemand damit beigetreten ist – für eine zweite Person
  erzeugt der Besitzer einen zweiten Link. Wer schon Mitglied ist, verbraucht
  den Link dabei nicht.
- **Rollen** – Besitzer dürfen den Gefrierschrank umbenennen, Mitglieder
  entfernen und den Gefrierschrank löschen. Mitglieder dürfen Artikel verwalten,
  den Gefrierschrank wechseln und ihn verlassen.
- **Mehrere Gefrierschränke** – auf der Seite „Gefrierschrank“ legt das Plus
  neben „Meine Gefrierschränke“ einen weiteren Gefrierschrank an (mit drei
  Schubladen), und zwischen allen lässt sich dort wechseln; die eigenen
  Gefrierschränke können umbenannt werden. Der aktive Gefrierschrank liegt in
  der Session (`App\Support\FreezerContext`); der eigene Gefrierschrank
  gewinnt, solange nicht explizit gewechselt wurde. Auch auf der Seite „Teilen“
  lässt sich zwischen den Gefrierschränken wechseln; die Seite bleibt dabei
  stehen und zeigt sofort die Mitglieder und Einladungen des gewählten
  Gefrierschranks.
- **Inventar über alle Gefrierschränke** – die Seite „Inventar“ listet die
  Artikel aller zugänglichen Gefrierschränke – eigene und geteilte – in einer
  Liste. Das Filter-Icon neben dem Suchfeld öffnet die Auswahl nach
  Gefrierschrank und nach Kategorie (Fleisch, Fisch, Gemüse, Suppe, Soßen,
  Obst, Sonstiges); ein Punkt am Icon zeigt an, dass ein Filter
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
  Anfrage sie übernimmt (`App\Http\Middleware\SetLocale`); der Klick lädt die
  Seite neu, damit auch Kopfzeile und untere Leiste („Gefrierschrank“,
  „Inventar“, „MHD“) sofort in der neuen Sprache stehen. Ohne Wahl bleibt es bei
  Deutsch, Gäste und brandneue Konten sehen also weiterhin Deutsch. Auch die
  Mails richten sich nach dem Konto (`HasLocalePreference`), und ein neuer
  Gefrierschrank samt Schubladen bekommt seinen Namen in der Sprache des
  Augenblicks. Alle Texte liegen in `lang/de`, `lang/it` und `lang/en`; die
  deutschen Dateien sind die Quelle der Wahrheit, weil die Tests ihre Wortwahl
  prüfen.
- **Profil** – Profilbild, Sprache der App, Name und E-Mail, Passwortwechsel,
  Rechtliches, das Löschen des Kontos und ganz unten das Abmelden, in dieser
  Reihenfolge; das Bild wird beim Auswählen sofort gespeichert (`updatedAvatar()`
  in der Komponente) – ein neues Bild ersetzt das alte samt Datei, ein Knopf zum
  Speichern gibt es nicht. Angenommen werden JPG, PNG und WebP bis 10 MB, und
  was zu groß ist, wird vor dem Speichern verkleinert (`App\Support\AvatarImage`):
  höchstens 512 Pixel auf der längsten Kante – der Avatar ist im Kopf 36 und im
  Profil 64 Pixel breit –, im selben Format (ein PNG behält seine
  Durchsichtigkeit), ein Bild, das schon klein genug ist, bleibt unangetastet.
  Aus einem Handyfoto von mehreren Megabyte werden so wenige Kilobyte, und ein
  HEIC vom iPhone kommt als JPEG an, weil das `accept` des Feldes den Browser
  umrechnen lässt. Ausgeliefert wird der Avatar über `/avatars/{user}`
  aus dem privaten `local`-Disk; die URL trägt eine Version des gespeicherten
  Pfades, damit der Browser nach dem Austausch nicht das alte Bild zeigt. Ein
  zweiter Knopf löscht das Konto samt Profilbild, Sitzungen und eigenen
  Gefrierschränken (Art. 17 DSGVO) – abgesichert durch die Eingabe des
  Passworts und die Rückfrage der App.
- **Rechtliches** – Impressum, Datenschutzerklärung und Nutzungsbedingungen
  liegen unter `/impressum`, `/datenschutz` und `/nutzungsbedingungen` und sind
  ohne Anmeldung lesbar (`App\Http\Controllers\LegalController`). Die Texte
  stehen in `lang/<sprache>/legal.php`, die Kontaktadresse kommt aus der
  Umgebung (`LEGAL_OPERATOR_EMAIL`, siehe „Rechtliches“ weiter unten). Im
  Impressum steht bewusst nur diese eine Angabe: Name und Anschrift werden
  nicht veröffentlicht. Der Anbieter sitzt in Italien; die Texte nennen deshalb
  die italienischen Vorschriften (D.Lgs. 70/2003, D.Lgs. 196/2003, Codice del
  Consumo) neben der DSGVO.
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
- **App auf dem Startbildschirm (PWA)** – Coolventory lässt sich auf dem Handy zum
  Startbildschirm hinzufügen und läuft dann ohne Browserleiste
  (`public/manifest.json`: `display: standalone`, Start bei `/`, Markenfarbe als
  Statusleiste). Das Icon ist überall dasselbe Bild: `public/favicon.png`
  (512 × 512, die Schneeflocke in der Markenfarbe) trägt den Tab, das
  `apple-touch-icon` des iPhones und das Manifest. Eingebunden werden die drei
  Verweise in jedem Layout über `<x-app-icons />`. Die Datei ist randlos in der
  Markenfarbe gefüllt und die Flocke liegt in der Schutzzone, deshalb wird sie
  zugleich als maskierbares Icon angeboten, das Android in die Form des
  Launchers schneidet. Den Service Worker (`public/sw.js`, registriert in
  `resources/js/app.js`) verlangen Chrome, Edge und Android, bevor sie die
  Installation überhaupt anbieten; er legt Icon, Manifest und die Offline-Seite
  `public/offline.html` vorab in den Cache und beantwortet einen Aufruf ohne
  Netz mit ihr. Im Profil steht dazu der Abschnitt „App installieren“ – als
  Knopf, wo der Browser die Installation anbietet, und als Hinweis auf das
  Teilen-Menü auf dem iPhone.

## Technik

| Baustein | Version |
| --- | --- |
| PHP | 8.5 (Sail-Image `sail-8.5/app`) |
| Laravel | 13.35 |
| Livewire | 4.4 (die gesamte Oberfläche, kein JS-Framework) |
| Tailwind CSS | 4.x über `@tailwindcss/vite` |
| Vite | 8.x |
| MySQL | 8.4 |
| Tests | PHPUnit 12.5 – 206 Tests / 988 Assertions |

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
| `tests/Unit/AvatarImageTest.php` | Zielgröße eines verkleinerten Profilbilds (ohne GD prüfbar) |
| `tests/Feature/AuthenticationTest.php` | Login, Registrierung, Logout |
| `tests/Feature/EmailVerificationTest.php` | Bestätigungslink, Hinweisseite, erneutes Senden |
| `tests/Feature/PageRenderTest.php` | Alle Seiten rendern für angemeldete Nutzer |
| `tests/Feature/FreezerAccessTest.php` | Fremde Gefrierschränke/Schubladen/Artikel bleiben unerreichbar |
| `tests/Feature/DrawerPageTest.php` | Artikel einer Schublade anlegen, ändern, löschen (Rückfrage im Modal) |
| `tests/Feature/InventoryOverviewTest.php` | Übersicht über alle Gefrierschränke, Suche, Filter-Panel, Sortierung, Löschen mit Rückfrage |
| `tests/Feature/DeleteConfirmationTest.php` | Löschbestätigungen laufen über das Modal der App, nie über den Browser |
| `tests/Feature/ExpiringItemsTest.php` | Seite „MHD-Warnungen“ über alle Gefrierschränke, Zähler in der Fußleiste |
| `tests/Feature/SharingPageTest.php` | Einladen (jeder Klick ein neuer Link, verbrauchte Links verlassen die Liste), Umbenennen, Gefrierschrank wechseln (bleibt auf der Seite und zeigt dessen Mitglieder), Mitglieder entfernen, Verlassen, Löschen |
| `tests/Feature/ClosedFreezerTest.php` | Gefrierschränke anlegen, wechseln, umbenennen |
| `tests/Feature/InvitationTest.php` | Einladungslink annehmen, Ablauf, ein Link pro Person (keine Mehrfachnutzung) |
| `tests/Feature/ProfileTest.php` | Profildaten, Passwort, Profilbild (speichert beim Auswählen, verkleinert zu große Bilder, ersetzt das alte), Konto löschen |
| `tests/Feature/LanguageTest.php` | Sprachwahl im Profil (lädt die Seite neu, damit auch die untere Leiste folgt), Sprache aller Seiten und Mails |
| `tests/Feature/LegalPagesTest.php` | Impressum, Datenschutzerklärung, Nutzungsbedingungen: ohne Anmeldung lesbar, im Impressum steht nur die E-Mail-Adresse (kein Name, keine Anschrift, kein Telefon) und die öffentliche Adresse statt `localhost`, Warnhinweis ohne Kontaktadresse, italienische Vorschriften (D.Lgs. 70/2003, D.Lgs. 196/2003, Codice del Consumo), Platzhalter in allen drei Sprachen, Verlinkung |
| `tests/Feature/PwaTest.php` | App-Icon, Manifest, Service Worker und Offline-Seite: eine Bilddatei für Tab, Startbildschirm und PWA, in allen Layouts verlinkt, Offline-Seite in allen drei Sprachen, Installations-Hinweis im Profil |

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
                     AvatarImage (verkleinert hochgeladene Profilbilder),
                     Legal (Kontaktangabe, öffentliche Adresse und Platzhalter der
                     Rechtstexte)
lang/de, lang/it, lang/en    Alle Texte, ein Verzeichnis pro Sprache
resources/views/livewire/**   Blade-Templates der Komponenten
resources/views/components/** Wiederverwendbare UI-Bausteine (card, notice, avatar,
                              confirm-dialog für Löschbestätigungen, …)
resources/views/legal/**      Impressum, Datenschutzerklärung, Nutzungsbedingungen
                              (eigenes Layout, weil sie ohne Konto lesbar sind)
public/favicon.png, manifest.json
                              Icon der App (eine Datei für Tab, Startbildschirm
                              und die installierte PWA) und ihr Manifest
public/sw.js, offline.html    Service Worker (Offline-Seite und die gebauten
                              Dateien im Cache) und die Offline-Seite in den drei
                              Sprachen der App
database/factories, database/seeders
```

## Rechtliches

Die App bringt drei Seiten mit, die ohne Anmeldung erreichbar sind:

| Adresse | Inhalt |
| --- | --- |
| `/impressum` | Kontakt (nur die E-Mail-Adresse), Haftung (Art. 16/17 D.Lgs. 70/2003), Streitbeilegung (ADR) |
| `/datenschutz` | Verantwortlicher, Daten, Rechtsgrundlagen, Cookies, Empfänger, Speicherdauer, Rechte, Konto löschen |
| `/nutzungsbedingungen` | Was die App leistet, Konto, Regeln, Verfügbarkeit, Beendigung |

Die Texte liegen in `lang/de|it|en/legal.php` (deutsch ist die Quelle der
Wahrheit) und sind in Abschnitte gegliedert; gerendert werden sie von
`resources/views/legal/*` mit `resources/views/layouts/legal.blade.php`. Sie
gehen vom **italienischen Recht** aus, weil der Anbieter in Italien sitzt:
D.Lgs. 70/2003 für den Dienst, D.Lgs. 196/2003 (Codice Privacy) und die DSGVO
für die Daten, Codice del Consumo und D.Lgs. 130/2015 für Vertrag und
Streitbeilegung. Die drei Sprachen nennen dieselben Vorschriften.

**Was der Betreiber noch tun muss** (die Seiten sind Vorlagen, keine
Rechtsberatung – im Zweifel prüfen lassen):

1. **Kontaktadresse hinterlegen.** `LEGAL_OPERATOR_EMAIL` in der `.env` (siehe
   `.env.example`, `config/legal.php`). Fehlt die Adresse, zeigt das Impressum
   einen **Warnhinweis** – die App soll nicht ohne Kontaktmöglichkeit online
   gehen. Sonst steht dort bewusst nur diese eine Angabe: Der Anbieter sitzt in
   Italien, und eine Impressumspflicht wie § 5 DDG in Deutschland gibt es dort
   nicht; für ein privates, nicht kommerzielles Angebot verlangt italienisches
   Recht keine Anbieterangaben. Name und Anschrift werden deshalb **nicht
   veröffentlicht** (sie gehören in die `.env` oder den Passwort-Manager des
   Betreibers), die E-Mail-Adresse genügt als schnelle elektronische
   Kontaktaufnahme. Wer die Anwendung gewerblich betreibt, braucht die
   Pflichtangaben nach Art. 7 D.Lgs. 70/2003 und Art. 49 Codice del Consumo –
   dazu gehören dann Name, Anschrift, Partita IVA und die REA-Nummer.
2. **Öffentliche Adresse prüfen** (`LEGAL_SITE_URL`): Sie steht im
   Geltungsbereich des Impressums („Dieses Impressum gilt für Coolventory unter
   …“). Vorgabe ist `www.coolventory.com`. Bewusst nicht `APP_URL`: das ist die
   Adresse der laufenden Installation und lokal `http://localhost:8000` – im
   Impressum soll nicht `localhost` stehen.
3. **Hoster und Mail-Dienst eintragen** (`LEGAL_HOSTING_*`, `LEGAL_MAIL_*`).
   Beide sind Empfänger im Sinne der Datenschutzerklärung, und für beide
   braucht es eine Verarbeitung im Auftrag nach Art. 28 DSGVO
   (Auftragsverarbeitungsvertrag; in Italien „responsabile del trattamento“,
   bei Hostern und Mail-Diensten meist Teil der AGB). Fehlt die Angabe, steht
   in der Erklärung ein neutraler Text („beim Anbieter zu erfragen“) statt
   einer Lücke.
4. **Stand-Datum setzen** (`LEGAL_UPDATED_AT`, `JJJJ-MM-TT`) – es erscheint
   unter jeder der drei Seiten.
5. **Prüfen, ob die Texte zum eigenen Fall passen:** Verkauf oder
   Werbung (dann die Informationspflichten des Art. 7 D.Lgs. 70/2003 und des
   Art. 49 Codice del Consumo), ein kommerzieller Betrieb, ein anderer Hoster,
   weitere Dienste (Analytics, Karten, Schriften von fremden Servern),
   Minderjährige als Nutzer (in Italien liegt die Grenze für die Einwilligung
   bei 14 Jahren, Art. 2-quinquies D.Lgs. 196/2003; die App verlangt 16).

**Was die App dafür schon tut** (damit die Texte stimmen):

- Kein Tracking, keine Werbung, keine Analyse-Werkzeuge, keine Inhalte von
  fremden Servern (Schriften liegen im eigenen Build).
- Nur ein technisch notwendiges Cookie (`laravel_session`) und der Eintrag
  `color-scheme` im lokalen Speicher des Browsers – deshalb ist **kein
  Cookie-Banner** nötig (Art. 122 D.Lgs. 196/2003 und die Cookie-Richtlinien
  des Garante Privacy: technisch notwendige Cookies brauchen keine
  Einwilligung).
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
- Die Datenschutzerklärung nennt die italienische Aufsichtsbehörde mit
  Anschrift (Garante per la protezione dei dati personali, Rom) und weist auf
  den Weg zu den Gerichten hin (Art. 152 D.Lgs. 196/2003).

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
- **Sprachwechsel lädt die Seite neu:** Die Wahl im Profil antwortet mit einem
  Livewire-Redirect (`ProfilePage::setLocale()`) statt mit einem Update an Ort
  und Stelle. Kopfzeile und untere Leiste stehen im Layout
  (`resources/views/layouts/app.blade.php`) außerhalb der Komponenten und würden
  sonst in der alten Sprache stehen bleiben – „Gefrierschrank“, „Inventar“ und
  „MHD“ kommen erst mit der geladenen Seite in der neuen Sprache.
  `tests/Feature/LanguageTest.php` wacht darüber.
- **Löschbestätigungen:** Jede Löschbestätigung läuft über das Modal der App
  (`resources/views/components/confirm-dialog.blade.php`, `<x-confirm-dialog>`):
  Der Knopf davor dispatcht nur `confirm-dialog` mit einem eindeutigen Namen, die
  Aktion (`wire:click`) sitzt auf dem Bestätigungsknopf im Modal, das sich
  schließt, bevor die Anfrage rausgeht. `wire:confirm` bzw. `window.confirm()`
  kommen nirgends zum Einsatz – `tests/Feature/DeleteConfirmationTest.php` wacht
  darüber. Die Texte heißen `<bereich>.delete_confirm_title` und
  `<bereich>.delete_confirm_message`.
- **Icon und Manifest:** `public/favicon.png` ist die einzige Bildquelle der App
  (512 × 512). Tab (`rel="icon"`), `apple-touch-icon` und das Manifest
  (`public/manifest.json`) zeigen auf dieselbe Datei, damit das Icon nicht
  auseinanderlaufen kann; eingebunden werden alle drei in jedem Layout über
  `resources/views/components/app-icons.blade.php` (`<x-app-icons />`). Das
  Manifest liegt als statische Datei in `public/`: „.json“ liefert jeder
  Webserver mit dem richtigen Typ aus, „.webmanifest“ müsste ihm erst
  beigebracht werden. `tests/Feature/PwaTest.php` wacht darüber.
- **Offline und Installation (PWA):** `public/sw.js` ist die einzige Stelle, die
  entscheidet, was aus dem Cache kommt: Seiten der App nie – Artikel und MHD
  liegen auf dem Server, ein alter Stand wäre schlimmer als keiner –, dafür die
  gebauten Dateien aus `public/build` (ihre Namen tragen einen Hash und ändern
  sich nie), das Icon, das Manifest und die Offline-Seite
  `public/offline.html`. Die Offline-Seite ist eine statische Datei mit eigenem
  Stil und Skript; sie spricht die Sprache der App, die `resources/js/app.js`
  unter `app-locale` merkt, und folgt dem gespeicherten Farbschema unter
  `color-scheme` – dieselben Schlüssel wie `components/theme-script.blade.php`.
  Wer die Offline-Seite, das Icon oder das Manifest ändert, erhöht `VERSION` in
  `public/sw.js`, sonst bleibt auf den Geräten die alte Kopie liegen. Registriert
  wird der Worker in `resources/js/app.js` mit `updateViaCache: 'none'`, damit
  ein Deployment nicht auf den HTTP-Cache des Browsers warten muss. Der
  Service Worker läuft nur auf einer sicheren Herkunft: `https` oder
  `localhost`. Über eine Adresse im lokalen Netz (`http://192.168.…`) wird er
  nicht registriert – dann bietet der Browser auch keine Installation an. Der
  Abschnitt „App installieren“ im Profil wird von `resources/js/app.js`
  eingeblendet und ist mit `wire:ignore` gegen Livewire-Updates geschützt.
  `tests/Feature/PwaTest.php` wacht darüber.
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
  (`Invitation::VALID_DAYS`) und nur einmal einlösbar: Er verfällt, sobald
  jemand damit beigetreten ist (wer schon Mitglied ist, verbraucht ihn nicht).
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
- **Profilbilder:** Zum Verkleinern braucht PHP die GD-Erweiterung (im
  Sail-Image enthalten). Fehlt sie, wird das Bild unverändert gespeichert – die
  App läuft weiter, nur größer. Der Upload muss außerdem durch PHP passen:
  `upload_max_filesize` und `post_max_size` sollten mindestens 11 MB erlauben
  (Sail: 100M), sonst bricht Livewire ab, bevor die Prüfung der App greift.
