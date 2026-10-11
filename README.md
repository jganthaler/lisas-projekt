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
  dazu Gefrierschrank und Schublade. Beide MHD-Zähler der App-Hülle – die runde
  Zahl am MHD-Feld und der Hinweis oben rechts („X Artikel prüfen“) – zählen
  über alle zugänglichen Gefrierschränke hinweg, also dieselbe Menge wie die
  Liste. Artikel ohne Datum
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
  angemeldeten Nutzer. Die Mail ist deutsch (`VerifyEmail::toMailUsing()` im
  `AppServiceProvider`).
- **Profil** – Name, E-Mail, Passwortwechsel und Profilbild; der Avatar wird über
  `/avatars/{user}` aus dem privaten `local`-Disk ausgeliefert.
- **Oberfläche** – Buttons, Felder, Auswahlfelder und Kacheln teilen sich einen
  Eckenradius von 8 px (`--radius-control` in `@theme`, in den Views als
  `rounded-control`). Ein schmaler Ladebalken am oberen Rand zeigt laufende
  Livewire-Requests; Seitenwechsel über `wire:navigate` nutzen die Leiste von
  Livewire in derselben Farbe.

## Technik

| Baustein | Version |
| --- | --- |
| PHP | 8.5 (Sail-Image `sail-8.5/app`) |
| Laravel | 13.35 |
| Livewire | 4.4 (die gesamte Oberfläche, kein JS-Framework) |
| Tailwind CSS | 4.x über `@tailwindcss/vite` |
| Vite | 8.x |
| MySQL | 8.4 |
| Tests | PHPUnit 12.5 – 114 Tests / 429 Assertions |

Die Oberfläche besteht vollständig aus Livewire-Komponenten
(`app/Livewire/**`); die einzigen klassischen Routen sind der Logout, die
Avatar-Auslieferung und der signierte Bestätigungslink
(`VerifyEmailController`).

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
| `tests/Feature/ExpiringItemsTest.php` | Seite „MHD-Warnungen“ über alle Gefrierschränke, Zähler in Kopf- und Fußleiste |
| `tests/Feature/SharingPageTest.php` | Einladen, Umbenennen, Mitglieder entfernen, Verlassen, Löschen |
| `tests/Feature/ClosedFreezerTest.php` | Gefrierschränke anlegen, wechseln, umbenennen |
| `tests/Feature/InvitationTest.php` | Einladungslink annehmen, Ablauf, Mehrfachnutzung |
| `tests/Feature/ProfileTest.php` | Profildaten, Passwort, Avatar-Upload/Entfernen |

## Aufbau

```
app/
  Livewire/          Oberfläche: Auth (inkl. E-Mail-Hinweis), Freezer, Invitations,
                     Profile, Sharing, InventoryOverview, ExpiringItems,
                     Concerns\InteractsWithItems
  Http/Controllers/  Logout, Avatar-Auslieferung, VerifyEmailController (signierter Link)
  Models/            User, Freezer, FreezerMembership, Drawer, InventoryItem, Invitation
  Policies/          FreezerPolicy, DrawerPolicy, InventoryItemPolicy
  Support/           FreezerContext (aktiver Gefrierschrank in der Session)
resources/views/livewire/**   Blade-Templates der Komponenten
resources/views/components/** Wiederverwendbare UI-Bausteine (card, notice, avatar, …)
database/factories, database/seeders
```

## Hinweise

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
