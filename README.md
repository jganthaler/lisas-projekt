# Coolventory

Geteilte Gefrierschrank-Verwaltung für den Haushalt: Wer hat was eingefroren und
wann läuft das MHD ab? Coolventory beantwortet diese Fragen für alle, die sich
einen Gefrierschrank teilen – inklusive Einladungen, Rollen und MHD-Ampel.

## Funktionen

- **Gefrierschränke mit Schubladen** – jeder Gefrierschrank hat Schubladen, in
  denen die Artikel liegen. Die Startseite zeigt zu jeder Schublade ihren Inhalt:
  Name und Menge, alphabetisch untereinander – gelesen wird dort nur, geändert
  wird ein Artikel in der Schublade selbst. Beim Öffnen sieht man sofort, welche
  Artikel bald ablaufen.
- **MHD-Ampel** – Artikel sind automatisch *abgelaufen*, *bald fällig* (≤ 7 Tage),
  *frisch* oder *ohne MHD*. Die Seite „MHD-Warnungen“ (unten rechts in der
  Navigation) sammelt alle kritischen Artikel über alle Gefrierschränke hinweg –
  unabhängig davon, welcher Gefrierschrank gerade aktiv ist; jede Zeile nennt
  dazu Gefrierschrank und Schublade. Der MHD-Zähler der App-Hülle – die runde
  Zahl am MHD-Feld unten in der Navigation – zählt über alle zugänglichen
  Gefrierschränke hinweg, also dieselbe Menge wie die Liste. Artikel ohne Datum
  zeigen in den Listen gar keine Angabe – statt „ohne MHD“ bleibt die Zeile
  leer. Wie ausführlich eine Zeile das MHD nennt, hängt von der Seite ab: die
  Schublade und die Warnliste zeigen Zustand, relativen Zähler und Datum, das
  Inventar nur das Datum und einen Zustand, solange er warnt. Auch in der
  Warnliste führt der Name der Schublade in die Schublade des Artikels – wer
  etwas aufbraucht, will oft gleich das MHD nachtragen.
- **Artikelverwaltung** – Menge erhöhen/verringern, umbenennen, verschieben,
  löschen und suchen. Ein Artikel, der im falschen Fach liegt, wandert in ein
  anderes Fach oder in einen anderen Gefrierschrank des Haushalts – Name, Menge,
  MHD und Kategorie bleiben dabei stehen. Vor dem Löschen fragt das Modal der
  App nach („Möchtest du … wirklich löschen?“) – die Rückfrage des Browsers
  kommt dabei nicht mehr zum Einsatz. Auch „−“ beim letzten Stück lässt die Zeile
  nicht ins Leere laufen: die App kennt keine Menge null, also öffnet der Knopf
  dieselbe Rückfrage wie der Papierkorb, und erst deren Antwort löscht den
  Artikel.
  Sortiert wird über den vorberechneten Sortierschlüssel `name_sort`,
  damit „Äpfel“ auch in MySQL an der richtigen Stelle steht. Den Schlüssel
  füllt das Model beim Speichern, die Spalte ist `NOT NULL`. Beim Anlegen eines
  Artikels hat das MHD-Feld vier Schnellauswahlen („3 Monate“, „6 Monate“,
  „1 Jahr“, „2 Jahre“): ein Tipp rechnet die Zeitspanne auf den heutigen Tag und
  füllt damit nur das Feld – gespeichert wird, was im Feld steht. Dieselben vier
  Auswahlen stehen im Formular eines Artikels, der schon liegt: dort füllen sie
  das MHD-Feld dieses Artikels (die Auswahl bleibt eine Schnellauswahl, das Datum
  lässt sich weiterhin von Hand setzen).
  Nach dem Speichern ist das Formular sofort für den nächsten Artikel bereit:
  Name, Menge und MHD beginnen von vorn, die Kategorie bleibt stehen – wer ein
  Fach mit Gemüse füllt, sortiert alles gleich ein –, und der Cursor steht wieder
  im Namensfeld. Ein ganzes Fach entsteht so Zeile für Zeile (tippen, Enter,
  tippen, Enter), ohne dass ein Feld erneut angetippt werden muss.
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
  greift. Jede Zeile trägt die Farbe ihrer Kategorie – Rot für Fleisch, Blau für
  Fisch, Grün für Gemüse, Gelb für Suppe, Braun für Soßen und Hellgrün für Obst;
  „Sonstiges“ und Artikel ohne Kategorie bleiben weiß. Bei mehreren
  Gefrierschränken nennt jede Zeile zusätzlich, woher der
  Artikel kommt. Zum MHD nennt sie nur das Datum: „frisch“ und der relative
  Zähler („MHD in 3 Tagen“) stehen dort nicht, eine Warnung („abgelaufen“,
  „bald abgelaufen“) schon. Der Name der Schublade in einer Zeile ist der Weg in
  diese Schublade: sie öffnet sich mit dem Formular genau dieses Artikels
  (`?item=<id>`, siehe `DrawerPage::mount()`), sodass ein gefundener Artikel
  nicht ein zweites Mal gesucht werden muss – dort lässt er sich umbenennen,
  verschieben oder löschen. Findet die Suche nichts, steht „Alle Artikel zeigen“
  direkt unter der Meldung und nimmt Suche und Filter mit einem Tipp zurück; ist
  das Inventar leer, führt der Knopf zum Gefrierschrank, wo der erste Artikel
  entsteht.
- **E-Mail-Verifizierung** – ein neues Konto muss seine E-Mail-Adresse
  bestätigen: Die Registrierung verschickt einen signierten Link, bis zur
  Bestätigung sperrt die `verified`-Middleware die App, und die Hinweisseite
  verschickt auf Wunsch einen neuen Link. Der Link hängt an Konto (`id`) und
  Adresse (`hash`), läuft nach einer Stunde ab und funktioniert nur für den
  angemeldeten Nutzer. Die Mail ist in der Sprache des Kontos
  (`VerifyEmail::toMailUsing()` im `AppServiceProvider`). Die bestätigte Adresse
  bleibt danach: Im Profil steht sie nur noch lesbar und lässt sich nicht mehr
  ändern (siehe „Hinweise“).
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
- **Profil** – Profilbild, Sprache der App, die Angaben (Name und die bestätigte
  E-Mail-Adresse), Passwortwechsel, Rechtliches, das Löschen des Kontos und ganz
  unten das Abmelden, in dieser Reihenfolge; die E-Mail-Adresse steht dort nur
  noch lesbar: Sie ist bestätigt, sie ist der Name des Kontos und der Empfänger
  der Links zum Zurücksetzen, ein Wechsel würde die Bestätigung entwerten. So
  bleibt jede Person bei der Adresse, mit der sie sich angemeldet und die sie
  bestätigt hat (`ProfilePage::updateProfile()` schreibt allein den Namen).
  Das Bild wird beim Auswählen sofort gespeichert (`updatedAvatar()`
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
  `rounded-control`). Ein Auswahlfeld malt seinen Pfeil selbst (`select.input`
  mit `--select-arrow` in `resources/css/app.css`): der Pfeil des Browsers klebt
  am Rand und kennt den Nachtmodus nicht. Ein schmaler Ladebalken am oberen Rand
  zeigt laufende Livewire-Requests; Seitenwechsel über `wire:navigate` nutzen die
  Leiste von Livewire in derselben Farbe.
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

- **Offline im Keller** – der Keller hat keinen Empfang, das Inventar muss aber
  trotzdem gepflegt werden. Jedes Gerät hält deshalb eine Kopie des Inventars:
  `resources/js/app.js` holt sie beim Besuch einer Seite der App über
  `GET /offline/snapshot` und legt sie in den lokalen Speicher des Browsers
  (zusammen mit dem Sitzungs-Token, den die Offline-Seite für das Senden
  braucht). Ohne Netz beantwortet der Service Worker jeden Aufruf mit
  `public/offline.html` – und die ist dort nicht nur ein Hinweis, sondern das
  Inventar selbst: Schubladen öffnen, Artikel anlegen, umbenennen, Menge ändern
  (beim letzten Stück fragt auch hier die Löschrückfrage, wie in der App mit
  Netz), verschieben und löschen. Jede Änderung wandert in eine Warteschlange und geht
  automatisch an `POST /offline/sync`, sobald wieder Netz da ist – oder per
  Knopf „Jetzt synchronisieren“; die Antwort bringt den frischen Stand mit, so
  dass die Kopie danach wieder aktuell ist. Ein Artikel, der ohne Netz entsteht,
  trägt eine vom Browser erzeugte `uuid`, unter der ihn der Server anlegt: ein
  zweites Senden legt ihn deshalb nicht doppelt an. Konflikte löst der letzte
  Stand (die zuletzt gesendete Änderung gewinnt), eine nicht erlaubte Änderung
  wird übersprungen statt den ganzen Stapel abzubrechen. Steht eine Seite der
  App noch offen, wenn die Änderungen ankommen, liest Livewire ihre Listen
  selbst neu (`offline-synced`). Der Hinweis „Keine Verbindung“ oben in der App
  führt in den Offline-Modus; beim Abmelden wird die Kopie vom Gerät gelöscht.

## Technik

| Baustein | Version |
| --- | --- |
| PHP | 8.5 (Sail-Image `sail-8.5/app`) |
| Laravel | 13.35 |
| Livewire | 4.4 (die gesamte Oberfläche, kein JS-Framework) |
| Tailwind CSS | 4.x über `@tailwindcss/vite` |
| Vite | 8.x |
| MySQL | 8.4 |
| Tests | PHPUnit 12.5 – 281 Tests / 1328 Assertions |

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
| `tests/Unit/InventoryItemStateTest.php` | MHD-Zustände, Sortierschlüssel und Schnellauswahl des MHD |
| `tests/Unit/AvatarImageTest.php` | Zielgröße eines verkleinerten Profilbilds (ohne GD prüfbar) |
| `tests/Feature/AuthenticationTest.php` | Login, Registrierung, Logout |
| `tests/Feature/EmailVerificationTest.php` | Bestätigungslink, Hinweisseite, erneutes Senden |
| `tests/Feature/PageRenderTest.php` | Alle Seiten rendern für angemeldete Nutzer |
| `tests/Feature/FreezerAccessTest.php` | Fremde Gefrierschränke/Schubladen/Artikel bleiben unerreichbar |
| `tests/Feature/DrawerPageTest.php` | Artikel einer Schublade anlegen (Schnellauswahl für das MHD, Kategorie bleibt für den nächsten Artikel stehen, Cursor zurück ins Namensfeld), ändern (mit denselben Schnellauswahlen für das MHD), verschieben (in ein anderes Fach oder einen anderen Gefrierschrank), löschen (Rückfrage im Modal – auch über „−“ beim letzten Stück), einen Artikel über `?item=<id>` direkt öffnen (fremde und erfundene IDs ändern nichts) |
| `tests/Feature/InventoryOverviewTest.php` | Übersicht über alle Gefrierschränke, Suche, Filter-Panel, Sortierung, MHD nur als Datum (ohne „frisch“ und ohne relativen Zähler), Farbe der Zeile nach Kategorie („Sonstiges“ und Artikel ohne Kategorie bleiben weiß), kein Verschieben aus der Übersicht, Löschen mit Rückfrage, Zeile führt in die Schublade des Artikels, leere Liste bietet den Weg zurück |
| `tests/Feature/DeleteConfirmationTest.php` | Löschbestätigungen laufen über das Modal der App, nie über den Browser |
| `tests/Feature/ExpiringItemsTest.php` | Seite „MHD-Warnungen“ über alle Gefrierschränke, Zähler in der Fußleiste, Zeile führt in die Schublade des Artikels |
| `tests/Feature/SharingPageTest.php` | Einladen (jeder Klick ein neuer Link, verbrauchte Links verlassen die Liste), Umbenennen, Gefrierschrank wechseln (bleibt auf der Seite und zeigt dessen Mitglieder), Mitglieder entfernen, Verlassen, Löschen |
| `tests/Feature/ClosedFreezerTest.php` | Gefrierschränke anlegen, wechseln, umbenennen; Inhalt jeder Schublade auf der Startseite (Name und Menge, alphabetisch, nichts zu bearbeiten) |
| `tests/Feature/OpenFreezerTest.php` | Schubladen des geöffneten Gefrierschranks: anlegen (ohne Namen nicht), umbenennen, löschen (eine volle Schublade nicht – die Meldung nennt sie), fremde und nicht aktive Gefrierschränke bleiben unerreichbar, Mitglieder dürfen mitarbeiten |
| `tests/Feature/InvitationTest.php` | Einladungslink annehmen, Ablauf, ein Link pro Person (keine Mehrfachnutzung) |
| `tests/Feature/ProfileTest.php` | Profilname, bestätigte E-Mail-Adresse (nur lesbar, bleibt unverändert, ihr Häkchen steht rechts davon), Passwort, Profilbild (speichert beim Auswählen, verkleinert zu große Bilder, ersetzt das alte), Konto löschen |
| `tests/Feature/LanguageTest.php` | Sprachwahl im Profil (lädt die Seite neu, damit auch die untere Leiste folgt), Sprache aller Seiten und Mails, Texte der Kategorien und der MHD-Schnellauswahl in allen drei Sprachen |
| `tests/Feature/LegalPagesTest.php` | Impressum, Datenschutzerklärung, Nutzungsbedingungen: ohne Anmeldung lesbar, im Impressum steht nur die E-Mail-Adresse (kein Name, keine Anschrift, kein Telefon) und die öffentliche Adresse statt `localhost`, Warnhinweis ohne Kontaktadresse, italienische Vorschriften (D.Lgs. 70/2003, D.Lgs. 196/2003, Codice del Consumo), Platzhalter in allen drei Sprachen, Verlinkung |
| `tests/Feature/OfflineTest.php` | Offline-Modus: Schnappschuss des Inventars (nur die eigenen und geteilten Gefrierschränke, in der Sprache des Kontos, mit den Wörtern der Oberfläche), Übernahme der Änderungen (Anlegen unter der `uuid` des Browsers – zweimal gesendet bleibt ein Artikel, Ändern, Verschieben, MHD entfernen, Löschen), Fremdes bleibt unberührt und wird übersprungen statt den Stapel abzubrechen, Menge nie unter 1 |
| `tests/Feature/PwaTest.php` | App-Icon, Manifest, Service Worker und Offline-Seite: eine Bilddatei für Tab, Startbildschirm und PWA, in allen Layouts verlinkt, Offline-Seite in allen drei Sprachen (samt Inventar-Modus), Hinweis und Weg in den Offline-Modus, Kopie des Inventars verschwindet beim Abmelden, Installations-Hinweis im Profil |

## Aufbau

```
app/
  Livewire/          Oberfläche: Auth (inkl. E-Mail-Hinweis), Freezer, Invitations,
                     Profile, Sharing, InventoryOverview, ExpiringItems,
                     Concerns\InteractsWithItems,
                     Concerns\FollowsOfflineChanges (jede Liste liest nach einem
                     Offline-Abgleich neu)
  Http/Controllers/  Logout, Avatar-Auslieferung (nur angemeldet), LegalController
                     (Impressum, Datenschutz, Nutzungsbedingungen),
                     VerifyEmailController (signierter Link),
                     OfflineController (Schnappschuss des Inventars und Übernahme
                     der Offline-Änderungen)
  Http/Middleware/   SetLocale (Sprache der Anfrage aus dem Konto)
  Models/            User, Freezer, FreezerMembership, Drawer, InventoryItem, Invitation
  Policies/          FreezerPolicy, DrawerPolicy, InventoryItemPolicy
  Support/           FreezerContext (aktiver Gefrierschrank in der Session),
                     OfflineInventory (das Inventar als Daten für die
                     Offline-Seite und die Übernahme ihrer Änderungen),
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
public/sw.js, offline.html    Service Worker (die gebauten Dateien, Icon und
                              Manifest im Cache) und die Offline-Seite: ohne Netz
                              ist sie das Inventar selbst, in den drei Sprachen
                              der App und mit eigener Warteschlange für die
                              Änderungen
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
- **E-Mail-Adresse im Profil:** Die bestätigte Adresse lässt sich nicht mehr
  ändern – das Profil zeigt sie nur noch lesbar an. Sie ist der Name des Kontos
  und der Empfänger der Links zum Zurücksetzen des Passworts; ein Wechsel würde
  die Bestätigung stillschweigend auf eine unbestätigte Adresse übertragen.
  `ProfilePage::updateProfile()` schreibt deshalb allein den Namen, und die
  Komponente kennt gar kein Feld für die Adresse mehr – wer sich mit einer
  Adresse angemeldet und sie bestätigt hat, bleibt bei ihr. Sie steht in einem
  Kasten wie ein Feld, damit sie links beginnt wie eine Eingabe; das Häkchen der
  Bestätigung sitzt rechts davon am Rand (`justify-between`) und bleibt auch bei
  einer langen Adresse stehen (`shrink-0`).
  `tests/Feature/ProfileTest.php` wacht darüber.
- **Schnelleingabe des MHD:** Beim Anlegen eines Artikels füllen vier
  Schnellauswahlen das MHD-Feld („3 Monate“, „6 Monate“, „1 Jahr“, „2 Jahre“).
  Die Zeitspannen stehen in Monaten in `InventoryItem::BEST_BEFORE_PRESETS` (ein
  Jahr sind zwölf, zwei Jahre vierundzwanzig – eine Rechnung für alle vier), die
  Etiketten liegen in `lang/<sprache>/drawers.php` unter
  `drawers.quick_best_before`, und `DrawerPage::useBestBeforePreset()` füllt
  damit nur das Feld: gespeichert wird, was im Feld steht, ein genaues Datum
  lässt sich also weiterhin eintragen. Gerechnet wird ohne Monatsüberlauf, damit
  „3 Monate“ vom 31.08. der 30.11. ist und nicht der 01.12., und eine Auswahl,
  die die App nicht kennt, ändert gar nichts.
  `tests/Feature/DrawerPageTest.php`,
  `tests/Unit/InventoryItemStateTest.php` und
  `tests/Feature/LanguageTest.php` wachen darüber.
- **Inhalt der Schubladen auf der Startseite:** Die Zeile einer Schublade
  (`resources/views/livewire/freezer/closed-freezer.blade.php`) nennt ihren
  Inhalt alphabetisch untereinander – nur Name und Menge, nicht mehr die Zahl der
  Artikel. Geändert wird dort nichts: die ganze Zeile bleibt ein Link in die
  Schublade (`drawers.show`), in der die Zeile eines Artikels ihre Knöpfe hat.
  Sortiert liefert die Relation (`Drawer::items()` über `name_sort`), und geladen
  werden alle Schubladen samt Inhalt mit einer Abfrage (`ClosedFreezer::render()`
  mit `with('items')`), damit die Seite nicht mit jeder Schublade eine weitere
  stellt. Eine leere Schublade sagt das selbst (`drawers.empty`), gezählt wird
  nur noch oben am Gefrierschrank (`freezer.summary`).
  `tests/Feature/ClosedFreezerTest.php` wacht darüber.
- **Artikel verschieben:** Ein Artikel wechselt seinen Platz, ohne neu angelegt zu
  werden – über das Verschieben-Symbol der Zeile in der Schublade. Zur Wahl
  stehen alle Fächer des Haushalts, auch die der anderen Gefrierschränke und die
  geteilten (`DrawerPage::moveTargets()`); das eigene Fach fehlt, dort läge der
  Artikel ja schon. Mit mehr als einem Gefrierschrank stehen sie als Gruppe
  (`<optgroup>`) über ihren Fächern, weil zwei Gefrierschränke beide eine
  „Schublade 1“ haben können. `DrawerPage::moveItem()` prüft die Wahl gegen diese
  Liste – ein fremdes oder erfundenes Fach ändert also nichts – und schreibt die
  Fremdschlüsselspalte über die Relation (`drawer()->associate()`), weil
  `drawer_id` nicht in der `#[Fillable]`-Liste des Models steht. Bearbeiten und
  Verschieben schließen sich gegenseitig aus: eine Zeile zeigt immer nur eines
  der beiden Formulare. `tests/Feature/DrawerPageTest.php` wacht darüber.
- **MHD in der Zeile:** Die Zeile eines Artikels
  (`resources/views/components/item-row.blade.php`) kennt zwei Formen des
  MHD-Hinweises. Die volle nennt Zustand („frisch“, „bald abgelaufen“, …), den
  relativen Zähler („MHD in 3 Tagen“) und das Datum; sie steht in der Schublade
  und auf der Seite „MHD-Warnungen“. Das Inventar liest die Zeile mit
  `:mhd-warnings-only="true"` (`InventoryOverview`): dort steht nur das Datum,
  und der Zustand nur, solange er warnt. „frisch“ und der Zähler sagen in einer
  Liste nichts, in der das Datum ohnehin daneben steht.
  `tests/Feature/InventoryOverviewTest.php` wacht über die kurze Form,
  `tests/Feature/DrawerPageTest.php` und `tests/Feature/ExpiringItemsTest.php`
  über die volle.
- **Farbe der Kategorie:** Nur das Inventar malt eine Zeile in der Farbe der
  Kategorie ihres Artikels (`InventoryItem::categoryTintClass()`): Rot für
  Fleisch, Blau für Fisch, Grün für Gemüse, Gelb für Suppe, Braun für Soßen und
  Hellgrün für Obst. Die Töne liegen als `--color-cat-*` in
  `resources/css/app.css` – im hellen Schema blass genug für die fast schwarze
  Schrift der Zeile, im dunklen tauscht der `.dark`-Block die Werte gegen dunkle
  Töne, sodass eine einzige Utility-Klasse beide Schemata bedient. Die Metazeile
  einer getönten Zeile tritt dabei eine Stufe dunkler (frost-700 statt
  frost-500), weil das gedämpfte Grau auf einem Ton keine 4.5:1 mehr erreicht.
  „Sonstiges“
  und ein Artikel ohne Kategorie bekommen keine Klasse: die Karte der Liste ist
  schon weiß. Die Zeile liest die Farbe nur mit `:category-tint="true"`
  (`InventoryOverview`); Schublade und Warnliste bleiben weiß.
  `tests/Feature/InventoryOverviewTest.php` wacht über die Farben,
  `tests/Feature/DrawerPageTest.php` und `tests/Feature/ExpiringItemsTest.php`
  darüber, dass die anderen Listen weiß bleiben.
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
- **Offline-Modus:** Die Offline-Seite (`public/offline.html`) ist die einzige
  Stelle, die Artikel ohne Server zeigt. Sie arbeitet ausschließlich mit der
  Kopie im lokalen Speicher des Browsers (`offline-snapshot`), die
  `resources/js/app.js` beim Besuch einer Seite der App über
  `GET /offline/snapshot` auffrischt – sie ist damit nur so frisch wie der letzte
  Besuch mit Netz. Solange eine Änderung wartet (`offline-queue`), wird die Kopie
  nicht neu geschrieben: erst sendet `POST /offline/sync` den Stapel, und die
  Antwort ersetzt die Kopie. Scheitert das (kein Netz, abgelaufene Sitzung),
  bleiben Änderungen und Kopie erhalten, und die Seite sagt es. Ändern zwei
  Geräte denselben Artikel ohne Netz, gewinnt der zuletzt gesendete Stand. Beim
  Abmelden löscht die App die Kopie vom Gerät (die Anmeldeseiten tragen dafür
  `offline-clear`), wartende Änderungen gehen damit verloren.
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
