# Coolventory

Geteilte Gefrierschrank-Verwaltung für den Haushalt: Wer hat was eingefroren und
wann läuft das MHD ab? Coolventory beantwortet diese Fragen für alle, die sich
einen Gefrierschrank teilen – inklusive Einladungen, Rollen und MHD-Ampel.

## Funktionen

- **Gefrierschränke mit Schubladen** – jeder Gefrierschrank hat Schubladen, in
  denen die Artikel liegen. Die Startseite zeigt zu jeder Schublade ihren Inhalt:
  die Namen, alphabetisch untereinander und auf derselben Linie wie der Name der
  Schublade (der Einzug ist bei jeder Schublade derselbe) – die Menge steht in
  der Schublade selbst. Gelesen wird dort nur, geändert wird ein Artikel in der
  Schublade selbst. Dort steht er in zwei Zeilen – der Name und darunter sein
  MHD als Datum („MHD: 15.12.2026“) –, rechts daneben die Menge mit ihren beiden
  Knöpfen und ein
  Menü (⋮) mit Bearbeiten, Löschen und Verschieben.
- **MHD-Ampel** – Artikel sind automatisch *abgelaufen*, *bald fällig* (≤ 7 Tage),
  *frisch* oder *ohne MHD*. Die Seite „MHD-Warnungen“ (unten rechts in der
  Navigation) sammelt alle kritischen Artikel über alle Gefrierschränke hinweg –
  unabhängig davon, welcher Gefrierschrank gerade aktiv ist; jede Zeile nennt
  dazu Gefrierschrank und Schublade. Der MHD-Zähler der App-Hülle – die runde
  Zahl am MHD-Feld unten in der Navigation – zählt über alle zugänglichen
  Gefrierschränke hinweg, also dieselbe Menge wie die Liste. Artikel ohne Datum
  lassen die Stelle des Datums nicht leer: dort steht „Kein MHD“. Die Zeile
  einer Liste ist drei Zeilen lang: der Name, darunter das MHD
  mit dem Etikett des Feldes („MHD: 15.12.2026“) und darunter der Ort –
  Gefrierschrank und Schublade mit Komma. Die zweite Zeile bleibt still: sie
  nennt das Datum und sonst nichts. Nur das Inventar stellt daneben, was warnt
  („abgelaufen“, „bald abgelaufen“) – die Warnliste trägt den Zustand in den
  Überschriften ihrer Abschnitte („Abgelaufen“, „Bald abgelaufen“), und die
  Schublade selbst nennt nur das Datum. Der Ort
  ist reiner Text – Gefrierschrank und Schublade stehen in derselben Schrift und
  Farbe nebeneinander und der Name der Schublade ist kein Link; in die Schublade
  des Artikels führt das Menü (⋮) der Zeile – wer etwas aufbraucht, will oft
  gleich das MHD nachtragen.
- **Artikelverwaltung** – Menge erhöhen/verringern, umbenennen, verschieben,
  löschen und suchen. Jede Zeile einer Liste trägt dafür ihr Menü (⋮) mit
  Bearbeiten, Löschen und Verschieben. Ein Artikel, der im falschen Fach liegt,
  wandert in ein anderes Fach oder in einen anderen Gefrierschrank des Haushalts
  – Name, Menge, MHD und Kategorie bleiben dabei stehen. Vor dem Löschen fragt
  das Modal der App nach („Möchtest du … wirklich löschen?“) – die Rückfrage des
  Browsers kommt dabei nicht mehr zum Einsatz. Auch „−“ beim letzten Stück lässt
  die Zeile nicht ins Leere laufen: die App kennt keine Menge null, also öffnet
  der Knopf dieselbe Rückfrage wie der Eintrag „Löschen“ im Menü, und erst deren
  Antwort löscht den Artikel.
  Sortiert wird über den vorberechneten Sortierschlüssel `name_sort`,
  damit „Äpfel“ auch in MySQL an der richtigen Stelle steht. Den Schlüssel
  füllt das Model beim Speichern, die Spalte ist `NOT NULL`. Beim Anlegen eines
  Artikels hat das MHD-Feld vier Schnellauswahlen unter dem Etikett „MHD
  Schnellauswahl“ („3 Monate“, „6 Monate“, „1 Jahr“, „2 Jahre“): ein Tipp
  rechnet die Zeitspanne auf den heutigen Tag und füllt damit nur das Feld –
  gespeichert wird, was im Feld steht. Dieselben vier Auswahlen stehen im
  Formular eines Artikels, der schon liegt: dort füllen sie das MHD-Feld dieses
  Artikels (die Auswahl bleibt eine Schnellauswahl, das Datum lässt sich
  weiterhin von Hand setzen).
  Das Formular eines Artikels, der schon liegt, endet mit zwei gleich großen
  Knöpfen: „Abbrechen“ links, „Speichern“ rechts, jeder über die halbe Breite
  des Formulars – die Breite eines Knopfes hängt nicht an der Länge seiner
  Beschriftung. Dasselbe Paar trägt das Verschieben-Formular („Abbrechen“ links,
  „Verschieben“ rechts).
  Die Schublade öffnet sich mit ihrem Inhalt, nicht mit dem Formular: das Plus
  rechts neben ihrem Namen bringt das Formular für einen neuen Artikel hoch
  (`DrawerPage::startAddingItem()`), und solange es offen steht, ist das Plus das
  Kreuz, das es wieder schließt (Escape tut dasselbe) – dieselbe Form hat schon
  das Plus der Gefrierschränke, und der Inhalt behält auf einem Telefon den Platz.
  Solange das Formular offen steht, steht es allein: der Inhalt der Schublade
  darunter tritt weg, damit nichts von dem Feld wegführt, das gerade ausgefüllt
  wird – die Liste kommt erst mit dem Kreuz zurück.
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
- **Mehrere Gefrierschränke** – das Feld „Meine Gefrierschränke“ auf der Seite
  „Gefrierschrank“ zeigt den aktiven Gefrierschrank als hervorgehobenen Block
  (die Kachel mit dem Icon der App – so groß wie das Icon einer Schublade
  darunter, damit beide symmetrisch untereinander stehen –, sein Name und der
  Pfeil nach unten). Ein Tipp auf den Block zieht die Liste aller
  Gefrierschränke des Kontos auf – in der
  Reihenfolge der Namen und mit dem Wort „aktiv“ am aktiven; ein Tipp auf eine
  Zeile wechselt, danach lädt die Seite neu. Pfeile links und rechts neben dem
  Namen gibt es nicht. Die Kopfzeile des Feldes trägt rechts das Plus für einen
  weiteren Gefrierschrank (mit drei Schubladen) und das Menü (⋮) des aktiven,
  hinter dem er sich umbenennen und löschen lässt – beides nur beim eigenen
  Gefrierschrank, ein Mitglied sieht dort kein Menü. Wem ein Gefrierschrank
  gehört, steht nur auf der Seite „Teilen“; eine zweite Karte mit Zählern trägt
  die Seite nicht. Der aktive Gefrierschrank liegt in
  der Session (`App\Support\FreezerContext`); der eigene Gefrierschrank
  gewinnt, solange nicht explizit gewechselt wurde. Auch auf der Seite „Teilen“
  lässt sich zwischen den Gefrierschränken wechseln; die Seite bleibt dabei
  stehen und zeigt sofort die Mitglieder und Einladungen des gewählten
  Gefrierschranks.
- **Inventar über alle Gefrierschränke** – die Seite „Inventar“ listet die
  Artikel aller zugänglichen Gefrierschränke – eigene und geteilte – in einer
  Liste. Das Filter-Icon neben dem Suchfeld ist ein Quadrat: so breit wie hoch
  (`h-11 w-11`, die Quadratgröße der App), und es steht mittig neben dem Feld,
  das ein paar Pixel höher ist als es. Es öffnet die Auswahl nach
  Gefrierschrank und nach Kategorie (Fleisch, Fisch, Gemüse, Suppe, Soßen,
  Obst, Sonstiges); ein Punkt am Icon zeigt an, dass ein Filter
  greift. Dort steht auch die Reihenfolge der Liste zur Wahl: Name (die
  Voreinstellung), Kategorie oder MHD („Nach MHD“: was zuerst abläuft, steht
  oben) – drei gleich breite Felder, das Etikett in jedem mittig. Sortieren
  schränkt nichts ein: der Punkt am Icon bleibt dabei dunkel, und „Alle Artikel
  zeigen“ nimmt Suche und Filter zurück, nicht die Reihenfolge. Nach Kategorie
  steht jede Kategorie als eigene Überschrift über ihren Artikeln – die Artikel
  einer Kategorie alphabetisch untereinander, eine leere Kategorie ohne
  Überschrift. Artikel ohne Kategorie stehen unter „Sonstiges“, ohne dass ihnen
  eine Kategorie geschrieben wird. Nach MHD steht die Liste in den Abschnitten
  der restlichen Haltbarkeit – „Aufzubrauchen innerhalb 1 Monats“ (abgelaufene
  Artikel stehen hier), „Aufzubrauchen innerhalb 6 Monate“ und „Noch länger
  haltbar“ –, und Artikel ohne MHD schließen sie unter „Kein MHD hinterlegt“;
  in jedem Abschnitt steht, was zuerst abläuft, oben, und ein Abschnitt ohne
  Artikel bekommt keine Überschrift.
  Jede Zeile ist weiß und drei Zeilen lang: der Name, darunter das MHD mit dem
  Etikett des Feldes („MHD: 15.12.2026“; „frisch“ und der relative Zähler „MHD
  in 3 Tagen“ stehen hier nicht, eine Warnung „abgelaufen“ oder „bald
  abgelaufen“ schon) und darunter der Ort. Bei mehreren Gefrierschränken nennt
  die dritte Zeile den Gefrierschrank mit – mit Komma vor der Schublade –, sonst
  steht dort nur die Schublade. Rechts an der Zeile stehen die Menge mit ihren
  beiden Knöpfen und das Menü (⋮) mit Bearbeiten, Löschen und Verschieben.
  Der Ort ist reiner Text: Gefrierschrank und Schublade stehen in derselben
  Schrift und Farbe mit dem Komma dazwischen, der Name der Schublade ist kein
  Link. In die Schublade führt das Menü (⋮) der Zeile – sie öffnet sich mit dem
  Formular genau dieses Artikels (`?item=<id>`, siehe `DrawerPage::mount()`),
  sodass ein gefundener Artikel
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
  in der Komponente) – ein neues Bild ersetzt das alte samt Datei, einen Knopf zum
  Speichern gibt es nicht. Danach lädt die Seite neu (Livewire-Redirect mit
  `navigate: true`, wie beim Sprachwechsel): Der Avatar der Kopfzeile liegt im
  Layout außerhalb der Komponente und bliebe sonst beim alten Zustand stehen –
  beim ersten Bild also bei den Initialen, nach dem Löschen beim gelöschten Bild.
  Bedient wird es über einen einzigen Knopf („Profilbild
  bearbeiten“) neben dem Bild links: er öffnet das Menü der App
  (`resources/views/components/menu.blade.php`), und dessen Einträge folgen dem
  Zustand – „Profilbild hinzufügen“ nur ohne Bild, „Profilbild ändern“ und
  „Profilbild löschen“ nur mit Bild, jeder mit seinem Symbol. Angenommen werden
  JPG, PNG und WebP bis 10 MB, und
  was zu groß ist, wird vor dem Speichern verkleinert (`App\Support\AvatarImage`):
  höchstens 512 Pixel auf der längsten Kante – der Avatar ist im Kopf 36 und im
  Profil 64 Pixel breit –, im selben Format (ein PNG behält seine
  Durchsichtigkeit), ein Bild, das schon klein genug ist, bleibt unangetastet.
  Aus einem Handyfoto von mehreren Megabyte werden so wenige Kilobyte, und ein
  HEIC vom iPhone kommt als JPEG an, weil das `accept` des Feldes den Browser
  umrechnen lässt. Ausgeliefert wird der Avatar über `/avatars/{user}`
  aus dem privaten `local`-Disk, und zwar mit dem Typ, den die Endung der
  gespeicherten Datei nennt (`image/jpeg`, `image/png`, `image/webp`): Ein Handy
  zeigt ein Bild ohne passenden Typ nicht an. Die URL trägt eine Version des
  gespeicherten Pfades, damit der Browser nach dem Austausch nicht das alte Bild
  zeigt; die Antwort darf nur im Speicher des eigenen Browsers liegen
  (`private, max-age=3600`). Der Kreis des Avatars trägt seine Initialen immer
  mit sich (`resources/views/components/avatar.blade.php`): Kann das Bild nicht
  geladen werden – kein Netz im Keller, eine verlorene Datei, eine abgelaufene
  Sitzung –, tritt der Kreis an seine Stelle, statt einen kaputten Rahmen zu
  zeigen; das gespeicherte Bild bleibt dabei unberührt.
  Gelöscht wird das Bild wie überall erst nach der Antwort im Dialog der App
  (`components/confirm-dialog.blade.php`), nie über den Dialog des Browsers. Der
  Knopf im Abschnitt „Konto löschen“ entfernt das Konto samt Profilbild,
  Sitzungen und eigenen Gefrierschränken (Art. 17 DSGVO) – abgesichert durch die
  Eingabe des Passworts und die Rückfrage der App.
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
- **Karten** – Alle Karten der App teilen einen einzigen Eckenradius von 8 px. Der
  Wert steht genau einmal: als `--radius-card` im `@theme`-Block von
  `resources/css/app.css` (Tailwind macht daraus `rounded-card`, `rounded-t-card`
  und die übrigen Seiten). Die Klassen, die eine Karte malen – `.card`,
  `.drawer-row` (die Zeile einer Schublade) und `.highlight-item` (der Schein auf
  einem frisch angelegten Artikel) – holen ihre Ecken von dort. Eine Ansicht
  rundet eine Karte nie selbst, sondern trägt die Klasse `card`; wer nur eine
  Seite braucht, nimmt dieselbe Vorgabe (`rounded-t-card` für das Band der
  Schubladenzeile). `--radius-control` bleibt für Buttons und Felder, eine Karte
  ist keines von beiden. Die Offline-Seite (`public/offline.html`) zeichnet ihr
  Inventar ohne das CSS der App und wiederholt den Wert deshalb in ihren eigenen
  Stilen. `tests/Feature/CardRadiusTest.php` wacht darüber.
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
  Netz), verschieben und löschen. Die Schublade öffnet sich auch hier mit ihrem
  Inhalt: das Plus neben ihrem Namen bringt das Formular für einen neuen Artikel
  hoch, und solange es offen steht, schließt dasselbe Plus es wieder – und die
  Überschrift „Inhalt“ und die Liste treten weg, wie in der App. Jede
  Änderung wandert in eine Warteschlange und geht
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
| Tests | PHPUnit 12.5 – 352 Tests / 1855 Assertions |

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
| `tests/Unit/InventoryItemStateTest.php` | MHD-Zustände, Sortierschlüssel, die drei Reihenfolgen des Inventars (unbekannte Schlüssel bleiben alphabetisch), die drei Fenster der restlichen Haltbarkeit (abgelaufen zählt ins erste, „Kein MHD“ schließt die Liste, jeder Schlüssel hat sein Etikett) und Schnellauswahl des MHD |
| `tests/Unit/AvatarImageTest.php` | Zielgröße eines verkleinerten Profilbilds (ohne GD prüfbar) und der Typ, den ein gespeichertes Bild an den Browser bekommt (aus der Endung; eine unbekannte bleibt offen) |
| `tests/Feature/AuthenticationTest.php` | Login (mit dem Auge am Passwortfeld, das nur das `type`-Attribut umschaltet), Registrierung, Logout |
| `tests/Feature/PasswordResetTest.php` | „Passwort vergessen?“ (die Antwort verrät nicht, ob es ein Konto gibt, ein zweiter Link kommt nicht sofort, irgendwann werden Anfragen ohne Konto abgewiesen), der Link in der Mail (einmaliger Token, die Adresse des Kontos steht schon im Formular, die Mail ist deutsch), neues Passwort mit dem Token (zu kurz oder nicht wiederholt wird abgelehnt, der Token eines anderen Kontos und ein zweiter Versuch mit demselben Link ebenso) |
| `tests/Feature/EmailVerificationTest.php` | Bestätigungslink, Hinweisseite, erneutes Senden |
| `tests/Feature/PageRenderTest.php` | Alle Seiten rendern für angemeldete Nutzer |
| `tests/Feature/FreezerAccessTest.php` | Fremde Gefrierschränke/Schubladen/Artikel bleiben unerreichbar |
| `tests/Feature/DrawerPageTest.php` | Artikel einer Schublade anlegen (die Schublade öffnet sich ohne Formular – das Plus neben ihrem Namen bringt es hoch und schließt es wieder, und solange es offen steht, steht es allein – der Inhalt darunter tritt weg –, Schnellauswahl für das MHD in vier gleich breiten Spalten, Kategorie bleibt für den nächsten Artikel stehen, Cursor zurück ins Namensfeld), ändern (mit denselben Schnellauswahlen für das MHD und den beiden Knöpfen „Abbrechen“ links und „Speichern“ rechts in zwei gleich breiten Spalten), verschieben (in ein anderes Fach oder einen anderen Gefrierschrank, dieselben zwei Knöpfe in derselben Größe – ohne Ziel steht „Abbrechen“ allein über die ganze Breite), löschen (Rückfrage im Modal – auch über „−“ beim letzten Stück), die Zeile eines Artikels (zwei Zeilen – der Name und darunter „MHD: <Datum>“, ohne Zustand und ohne relativen Zähler, ohne zweite Zeile ohne MHD, weiße Karte, Menge rechts, eigenes Menü (⋮) mit Bearbeiten, Löschen und Verschieben), der Weg zurück führt auf die Startseite des Gefrierschranks (nicht in das Bearbeiten der Schubladen), einen Artikel über `?item=<id>` direkt öffnen (fremde und erfundene IDs ändern nichts, `&move=1` bringt daneben das Verschieben-Formular dieses Artikels hoch) |
| `tests/Feature/InventoryOverviewTest.php` | Übersicht über alle Gefrierschränke, Suche, Filter-Panel, Sortierung nach Name, Kategorie und MHD (nur aufsteigend – „Nach MHD“, die drei Felder in drei gleich breiten Spalten mit mittigem Etikett, Artikel ohne Kategorie und ohne MHD ans Ende der Abfrage, unbekannte Schlüssel bleiben alphabetisch, Sortieren schränkt nichts ein), Gruppierung nach Kategorie (eine Überschrift je Kategorie mit der Zahl ihrer Artikel, die Artikel einer Kategorie alphabetisch untereinander, Artikel ohne Kategorie unter „Sonstiges“ ohne Eintrag in die Datenbank, leere Kategorien ohne Überschrift, jede andere Reihenfolge bleibt die flache Liste), Gruppierung nach MHD (drei Fenster der restlichen Haltbarkeit mit „Kein MHD hinterlegt“ zuletzt, je eine Überschrift mit der Zahl ihrer Artikel in der Reihenfolge der Fenster, innerhalb eines Fensters das frühere Datum oben und erst bei gleichem Datum der Name, ein Datum, das ein Artikel nachträglich bekommt, bringt ihn in sein Fenster, ein entferntes schließt die Liste, ein leeres Fenster ohne Überschrift, ein Filter lässt nur seine Fenster stehen, jede andere Reihenfolge – auch die nach Kategorie – bleibt die flache Liste), MHD nur als Datum mit dem Etikett des Feldes („MHD: <Datum>“, ohne „frisch“ und ohne relativen Zähler), drei Zeilen je Artikel (der Name, das MHD, Gefrierschrank und Schublade mit Komma), weiße Karte, eigenes Menü (⋮) mit Bearbeiten, Löschen und Verschieben (Bearbeiten und Verschieben führen mit `?item=<id>` bzw. `&move=1` in die Schublade des Artikels), Löschen mit Rückfrage, Ort der Zeile als reiner Text (Gefrierschrank und Schublade mit Komma, kein Link – in die Schublade führt das Menü), leere Liste bietet den Weg zurück, der Filter-Knopf als Quadrat neben dem Suchfeld (`h-11 w-11`, mittig – das Feld ist ein paar Pixel höher) |
| `tests/Feature/DeleteConfirmationTest.php` | Löschbestätigungen laufen über das Modal der App, nie über den Browser |
| `tests/Feature/CardRadiusTest.php` | Der Eckenradius der Karten: genau eine Vorgabe (`--radius-card`, 8 px) im Stylesheet der App, `.card`, `.drawer-row` und `.highlight-item` nehmen ihre Ecken daraus, keine Karte in einer Ansicht bringt einen eigenen Radius mit, und die Offline-Seite rundet ihre Karten wie die App |
| `tests/Feature/ActionPairTest.php` | Jede Zeile mit „Abbrechen“ und einer Aktion in allen Ansichten: „Abbrechen“ links, die Hauptaktion rechts, beide in zwei gleich breiten Spalten (`grid grid-cols-2 gap-2`, jeder Knopf `w-full`), keine Flex-Zeile mit `flex-1`, und hinter dem Abbrechen steht die Aktion; die Rückfrage vor dem Löschen behält ihre eigene Zeile und hält die Antwort trotzdem rechts (ab `sm` nebeneinander in umgekehrter Reihenfolge) |
| `tests/Feature/ExpiringItemsTest.php` | Seite „MHD-Warnungen“ über alle Gefrierschränke, Zähler in der Fußleiste, drei Zeilen je Artikel (der Name, „MHD: <Datum>“ ohne Zustand und ohne relativen Zähler, Gefrierschrank und Schublade mit Komma), weiße Karte, eigenes Menü (⋮) mit Bearbeiten, Löschen und Verschieben, Ort der Zeile als reiner Text (Gefrierschrank und Schublade mit Komma), Zeile führt über ihr Menü in die Schublade des Artikels |
| `tests/Feature/SharingPageTest.php` | Einladen (jeder Klick ein neuer Link, verbrauchte Links verlassen die Liste), Umbenennen, Gefrierschrank wechseln (bleibt auf der Seite und zeigt dessen Mitglieder), Mitglieder entfernen, Verlassen, Löschen |
| `tests/Feature/ClosedFreezerTest.php` | Gefrierschränke anlegen, wechseln, umbenennen und löschen; das Feld „Meine Gefrierschränke“ (die Kopfzeile mit dem Titel, dem Plus für einen weiteren Gefrierschrank und dem Menü (⋮) des aktiven, darunter der aktive Gefrierschrank als eigener Block mit Kachel und Icon (so groß wie der Kopf einer Schublade darunter: `h-10 w-10` mit dem Zeichen `h-5 w-5`, damit beide Icons symmetrisch untereinander stehen) – wem er gehört, steht nicht dabei –, die Fläche unter der Haarlinie trägt denselben Ton wie dieser Block (`bg-brand-50`, im dunklen Schema `bg-brand-950`), reicht über die volle Breite des Feldes und nimmt die unteren Ecken der Karte (`rounded-b-card`), solange kein Formular darunter steht, der Block zieht die Liste aller mit ihrer Markierung „aktiv“ auf, Pfeile links und rechts gibt es nicht, das Menü gehört zum aktiven Gefrierschrank und trägt Umbenennen und Löschen, ein Mitglied sieht dort kein Menü, und das Feld trägt ein Formular auf einmal); die Seite zählt nirgends; Inhalt jeder Schublade auf der Startseite (der Kopf mit Icon und fettem Namen auf dem hellen Grau des Feldes – das Band reicht über die volle Breite der Karte bis zur Trennlinie und nimmt die oberen Ecken der Karte (`rounded-t-card`, also `--radius-card`), das Icon sitzt auf der Tönung der App und die Liste darunter bleibt auf dem Weiß der Karte –, darunter die Namen alphabetisch, ohne Menge, nichts zu bearbeiten – die Namen beginnen auf der Linie des Schubladennamens, der Einzug ist Icon plus Abstand des Kopfes und bei jeder Schublade derselbe – und die Liste folgt jedem Anlegen, Umbenennen, Verschieben und Löschen); beide Formulare des Feldes (einen Gefrierschrank anlegen, ihn umbenennen) enden mit „Abbrechen“ links und der Aktion rechts in zwei gleich breiten Spalten |
| `tests/Feature/OpenFreezerTest.php` | Schubladen des geöffneten Gefrierschranks: anlegen (ohne Namen nicht), umbenennen, löschen (eine volle Schublade nicht – die Meldung nennt sie), jede Zeile trägt ihr eigenes Menü (⋮) mit Umbenennen und Löschen, das Löschen fragt weiterhin im Modal der App nach, fremde und nicht aktive Gefrierschränke bleiben unerreichbar, Mitglieder dürfen mitarbeiten; das Umbenennen-Formular endet mit „Abbrechen“ links und „Speichern“ rechts in zwei gleich breiten Spalten |
| `tests/Feature/InvitationTest.php` | Einladungslink annehmen, Ablauf, ein Link pro Person (keine Mehrfachnutzung) |
| `tests/Feature/AvatarDeliveryTest.php` | Auslieferung des Profilbilds an das Gerät: der Typ aus der Endung der Datei (jpg, jpeg, png, webp – ohne ihn zeigt ein Handy das Bild nicht an), inline statt Download, `private, max-age=3600`, nur für angemeldete Konten, 404 ohne Datei (das Konto bleibt dabei unberührt) |
| `tests/Feature/ProfileTest.php` | Profilname, bestätigte E-Mail-Adresse (nur lesbar, bleibt unverändert, ihr Häkchen steht rechts davon), Passwort, Profilbild (ein Knopf „Profilbild bearbeiten“ mit dem Menü der App – hinzufügen nur ohne Bild, ändern und löschen nur mit Bild, das Löschen über die Rückfrage der App, das Bild links folgt mit der geladenen Seite, die Kopfzeile auch, und der Kreis mit den Initialen wartet als Rückfall darunter; speichert beim Auswählen, verkleinert zu große Bilder, ersetzt das alte), Konto löschen |
| `tests/Feature/LanguageTest.php` | Sprachwahl im Profil (lädt die Seite neu, damit auch die untere Leiste folgt), Sprache aller Seiten und Mails, Texte der Kategorien, der Reihenfolgen des Inventars, der MHD-Schnellauswahl, des Profilbild-Menüs (Bearbeiten, Hinzufügen, Ändern, Löschen) und der drei Aktionen der Artikelzeile (Bearbeiten, Löschen, Verschieben) in allen drei Sprachen |
| `tests/Feature/LegalPagesTest.php` | Impressum, Datenschutzerklärung, Nutzungsbedingungen: ohne Anmeldung lesbar, im Impressum steht nur die E-Mail-Adresse (kein Name, keine Anschrift, kein Telefon) und die öffentliche Adresse statt `localhost`, Warnhinweis ohne Kontaktadresse, italienische Vorschriften (D.Lgs. 70/2003, D.Lgs. 196/2003, Codice del Consumo), Platzhalter in allen drei Sprachen, Verlinkung |
| `tests/Feature/OfflineTest.php` | Offline-Modus: Schnappschuss des Inventars (nur die eigenen und geteilten Gefrierschränke, in der Sprache des Kontos, mit den Wörtern der Oberfläche), Übernahme der Änderungen (Anlegen unter der `uuid` des Browsers – zweimal gesendet bleibt ein Artikel, Ändern, Verschieben, MHD entfernen, Löschen), Fremdes bleibt unberührt und wird übersprungen statt den Stapel abzubrechen, Menge nie unter 1 |
| `tests/Feature/PwaTest.php` | App-Icon, Manifest, Service Worker und Offline-Seite: eine Bilddatei für Tab, Startbildschirm und PWA, in allen Layouts verlinkt, Offline-Seite in allen drei Sprachen (samt Inventar-Modus, denselben vier gleich breiten Schnellauswahlen des MHD und einer Schublade, deren Formular hinter dem Plus wartet und dann allein steht – die Überschrift „Inhalt“ und die Liste treten weg, der Pfeil führt auf die Startseite des Gefrierschranks –), das Blatt eines Artikels („Abbrechen“ links, die Aktion rechts, beide in zwei gleich breiten Spalten), Hinweis und Weg in den Offline-Modus, Kopie des Inventars verschwindet beim Abmelden, Installations-Hinweis im Profil |

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
                     AvatarImage (verkleinert hochgeladene Profilbilder und nennt
                     den Typ der gespeicherten Datei),
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
- **Profilbild und Kopfzeile:** Ein gewähltes Bild wird sofort gespeichert, und
  die Antwort ist ein Livewire-Redirect (`navigate: true`) – wie beim
  Sprachwechsel, aus demselben Grund: Der Avatar der Kopfzeile steht im Layout
  außerhalb der Komponente (`ProfilePage::updatedAvatar()`,
  `ProfilePage::removeAvatar()`). Jede geladene Seite liest die Adresse des
  Bildes vom Konto (`User::avatarUrl()`, mit einer Version des gespeicherten
  Pfades) – deshalb sieht auch ein zweites Gerät dasselbe Bild, und nach einem
  neuen Bild ist die Adresse eine andere, sodass kein Gerät beim alten hängen
  bleibt. Der Kreis des Avatars trägt die Initialen als Rückfall unter dem Bild
  (`resources/views/components/avatar.blade.php`), und `/avatars/{user}` schickt
  den Typ der gespeicherten Datei. `tests/Feature/ProfileTest.php` und
  `tests/Feature/AvatarDeliveryTest.php` wachen darüber.
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
  Über ihnen steht „MHD Schnellauswahl“, damit gleich klar ist, wozu die vier
  Pillen gehören – in beiden Formularen und offline dasselbe Etikett
  (`drawers.quick_best_before.label`, in allen drei Sprachen).
  Die Zeitspannen stehen in Monaten in `InventoryItem::BEST_BEFORE_PRESETS` (ein
  Jahr sind zwölf, zwei Jahre vierundzwanzig – eine Rechnung für alle vier), die
  Etiketten liegen in `lang/<sprache>/drawers.php` unter
  `drawers.quick_best_before`, und `DrawerPage::useBestBeforePreset()` füllt
  damit nur das Feld: gespeichert wird, was im Feld steht, ein genaues Datum
  lässt sich also weiterhin eintragen. Gerechnet wird ohne Monatsüberlauf, damit
  „3 Monate“ vom 31.08. der 30.11. ist und nicht der 01.12., und eine Auswahl,
  die die App nicht kennt, ändert gar nichts. Die vier stehen in vier gleich
  breiten Spalten über die ganze Breite des MHD-Feldes, das Etikett mittig in
  jeder Pille (`grid-cols-4` in der Schubladen-Seite, `.chip` in
  `resources/css/app.css`, offline `.chips`/`.chip` in `public/offline.html`) –
  „1 Jahr“ ist damit so breit wie „3 Monate“, und in beiden Formularen, dem für
  einen neuen und dem für einen vorhandenen Artikel.
  `tests/Feature/DrawerPageTest.php`,
  `tests/Unit/InventoryItemStateTest.php`,
  `tests/Feature/LanguageTest.php` und `tests/Feature/PwaTest.php` wachen
  darüber.
- **Das Formular für einen neuen Artikel steht allein:** Solange es offen steht,
  ist es die ganze Schublade: der Inhalt darunter tritt weg
  (`resources/views/livewire/freezer/drawer-page.blade.php` – Formular und Liste
  stehen in einem `@if`/`@else`), damit nichts von dem Feld wegführt, das gerade
  ausgefüllt wird. Nach dem Speichern bleibt das Formular offen und die Liste
  damit weiterhin weg: ein Fach entsteht Zeile für Zeile, ohne dass die Liste
  dazwischensteht. Die Offline-Seite hält es genauso – `drawAddForm()` in
  `public/offline.html` nimmt die Überschrift „Inhalt“ (`data-contents`) und die
  Liste (`data-items`) gemeinsam weg, damit kein leerer „Inhalt“ über nichts
  steht.
  `tests/Feature/DrawerPageTest.php` und `tests/Feature/PwaTest.php` wachen
  darüber.
- **Das Feld „Meine Gefrierschränke“:** Es ist das erste Feld der Startseite
  (`resources/views/livewire/freezer/closed-freezer.blade.php`). Seine Kopfzeile
  trägt links den Titel und rechts zwei Knöpfe auf derselben Geister-Fläche
  (`btn btn-ghost btn-icon`): das Plus für einen weiteren Gefrierschrank
  (`startAddingFreezer()`, benannt über `freezer.add`) und das Menü (⋮,
  `resources/views/components/menu.blade.php`) des aktiven. Unter der Kopfzeile
  läuft eine Haarlinie (`border-t border-frost-100`) über die volle Breite des
  Feldes und trennt sie von dem Gefrierschrank, auf den sie sich bezieht; die
  Karte des Feldes trägt deshalb keine Polsterung, sondern jede Zeile bringt ihre
  eigene mit. Die Fläche unter der Haarlinie trägt denselben Ton wie der Block
  des aktiven Gefrierschranks (`bg-brand-50`, im dunklen Schema `bg-brand-950`):
  das Feld wirkt als ein Band und nicht als ein Block auf einer weißen Karte.
  Beide Stellen nennen dieselbe Klasse der Palette aus `resources/css/app.css`,
  nie einen eigenen Wert; Kopfzeile, Kachel, Name, Pfeil und Liste bleiben dabei
  unverändert. Solange kein Formular darunter steht, ist das Band das letzte
  Stück der Karte und nimmt ihre unteren Ecken (`rounded-b-card`, also
  `--radius-card`), damit der Ton darin bleibt; mit einem offenen Formular
  schließt dessen Haarlinie das Band, und die Ecken bleiben eckig. Darunter
  steht der aktive Gefrierschrank als Block auf der Fläche des Akzents
  (`rounded-2xl bg-brand-50`, im dunklen Schema `bg-brand-950`): links
  die Kachel mit dem Icon der App (`bg-brand-600`) – so groß wie der Kopf einer
  Schublade darunter (`h-10 w-10`, das Zeichen `h-5 w-5`), damit die beiden
  Icons der Startseite gleich groß übereinander stehen –, daneben sein Name in
  der größten Schrift der Seite (`text-base font-bold`) und rechts der Pfeil nach
  unten (`chevron-down`), der sich beim Aufziehen dreht (`x-bind:class`). Der
  Block ist ein Knopf und zieht die Liste aller Gefrierschränke auf
  (`x-data`/`x-show` in der Ansicht selbst, ohne eigenes Skript): jeder mit
  Kachel, Name und – beim aktiven – dem Häkchen und dem Wort „aktiv“
  (`aria-current`). Wem ein Gefrierschrank gehört, steht nirgends mehr im Feld:
  die Liste kennzeichnet den aktiven, und `freezer.owned` bzw. `freezer.shared`
  leben nur noch auf der Teilen-Seite
  (`resources/views/livewire/sharing/sharing-page.blade.php`). Die Liste steht im
  Fluss der Seite und schiebt die Schubladen nach unten, statt sie zu verdecken
  (ein schwebendes Panel wie das Menü würde auf einem Handy über der Liste der
  Schubladen liegen); ein Tipp auf eine Zeile wechselt (`selectFreezer()`), danach
  lädt die Seite neu und zeigt den Inhalt des gewählten Gefrierschranks. Pfeile
  links und rechts neben dem Namen gibt es nicht mehr: gewechselt wird allein über
  die Liste, die jeden Gefrierschrank des Kontos in der Reihenfolge der Namen
  nennt. Das Menü gehört dem aktiven Gefrierschrank: Umbenennen
  (`startRenaming()`) und Löschen. Beides erscheint nur beim eigenen
  Gefrierschrank (siehe `App\Policies\FreezerPolicy`), ein Mitglied sieht dort
  nichts und deshalb gar kein Menü. Das Anlegen steht nicht im Menü, sondern am
  Plus der Kopfzeile – es ist der einzige Weg zu einem weiteren Gefrierschrank.
  Das Löschen fragt über den Dialog der App nach
  (`resources/views/components/confirm-dialog.blade.php`) – der Dialog steht
  neben dem Menü, nie darin: das Panel des Menüs ist versteckt und würde ihn
  mitverstecken. Beide Formulare (anlegen, umbenennen) stehen unter dem Feld
  hinter einer Haarlinie (`border-t border-frost-100`), und es ist immer nur
  eines offen: `startAddingFreezer()` schließt das Umbenennen und umgekehrt. Nach
  dem Anlegen und dem Umbenennen lädt die Seite neu, weil der Name auch im Kopf
  der App steht (`resources/views/layouts/app.blade.php` liegt außerhalb der
  Komponente). Ein neuer Gefrierschrank bekommt seine drei Schubladen wie bisher
  (`Freezer::createFor()`), und gelöscht wird weiterhin über die vorhandene Logik
  (`deleteFreezer()`, danach übernimmt ein anderer Gefrierschrank des Kontos).
  `tests/Feature/ClosedFreezerTest.php` wacht über all das.
- **Inhalt der Schubladen auf der Startseite:** Die Zeile einer Schublade
  (`resources/views/livewire/freezer/closed-freezer.blade.php`) ist zweigeteilt.
  Oben steht ihr Kopf auf dem hellen Grau des Feldes: ein Band
  (`rounded-t-card bg-frost-200`) umfasst das Icon und den fett gesetzten
  Namen, rechts die Pille der Artikel, die Aufmerksamkeit brauchen, und den Pfeil
  in die Schublade; es läuft über die volle Breite der Karte bis zu der Haarlinie
  und endet dort. Das Icon hebt sich von dem Grau ab, weil es auf der Tönung der
  App sitzt (`bg-brand-50 text-brand-600`, in der dunklen Ansicht
  `dark:bg-brand-950 dark:text-brand-400`) statt auf dem Grau der Karte. Die
  Haarlinie (`border-t border-frost-100` – dieselbe, die auch die Karten der App
  zwischen Kopf und Rumpf ziehen, siehe den Filter des Inventars) ist damit die
  Unterkante des Bandes, und unter ihr bleibt der Rumpf auf dem Weiß der Karte.
  Das Polster der Karte tragen die beiden Teile selbst: die Karte gibt es ab
  (`gap-0 p-0`), das Band bringt `px-4 pb-3 pt-3.5` mit und der Rumpf
  `px-4 pb-3.5 pt-3` – zusammen sind das die 0.875rem oben und unten und die
  0.75rem über und unter der Haarlinie, die die Karte vorher hatte. So bleibt die
  Karte auf dem Smartphone wie auf dem großen Bildschirm ein Block, und das Grau
  reicht wirklich bis an ihren Rand. Unter der
  Linie liegt der Inhalt alphabetisch untereinander – nur die Namen, weder die
  Menge eines Artikels noch die Zahl der Artikel –, und er beginnt auf derselben
  Linie wie der Name der Schublade: der Kopf stellt sein Icon (2.5rem) mit dem
  Abstand des Kopfes (0.75rem) neben den Namen, und die Liste trägt genau diese
  Strecke als linken Einzug (`pl-13` = 13 × 0.25rem = 3.25rem). Beide Zahlen sind
  fest, so rückt jede Schublade gleich weit ein – auf dem Smartphone wie auf dem
  großen Bildschirm –, und Icon, Name und Haarlinie bleiben dabei, wo sie sind:
  nur die Namen bewegen sich.
  Die Menge steht in der Schublade selbst: die Startseite wird gelesen, nicht
  gezählt. Geändert wird dort
  nichts: die ganze Zeile bleibt ein Link in die Schublade (`drawers.show`), in
  der die Zeile eines Artikels ihre Knöpfe hat.
  Sortiert liefert die Relation (`Drawer::items()` über `name_sort`), und geladen
  werden alle Schubladen samt Inhalt mit einer Abfrage (`ClosedFreezer::render()`
  mit `with('items')`), damit die Seite nicht mit jeder Schublade eine weitere
  stellt. Die Liste entsteht bei jeder Ausgabe neu, folgt also jedem Anlegen,
  Umbenennen, Verschieben und Löschen – die Karte einer Schublade hat für Artikel
  keine eigenen Aktionen, gearbeitet wird in der Schublade selbst. Eine leere
  Schublade sagt das selbst (`drawers.empty`) – ohne den Einzug der Namen –, und
  gezählt wird auf der Startseite nirgends: die Zahl der Artikel steht in der
  Schublade selbst, und die Karte des aktiven Gefrierschranks ist fort (sein
  Name steht in der Liste unter „Meine Gefrierschränke“, mit „aktiv“).
  `tests/Feature/ClosedFreezerTest.php` wacht darüber.
- **Der Weg zurück aus einer Schublade:** Der Pfeil oben links in der Schublade
  (`resources/views/livewire/freezer/drawer-page.blade.php`) führt auf die
  Startseite des Gefrierschranks (`freezer.closed`), nicht in das Bearbeiten der
  Schubladen (`freezer.open`): eine Schublade gehört zum Reiter „Gefrierschrank“
  der Fußleiste (sie hebt ihn hier hervor), und der Pfeil verspricht mit
  „Zurück zum Gefrierschrank“ (`drawers.back_aria`) genau diese Seite. Das
  Bearbeiten der Schubladen hängt am Fuß der Startseite, nicht am Rücken der
  Schublade – wer von dort in eine Schublade steigt, landet also nicht wieder in
  der Verwaltung. Die Offline-Seite hält es genauso (`public/offline.html`, der
  Pfeil des Offline-Fachs zeigt auf `/freezer`; ohne Netz zeigt diese Adresse das
  Inventar, der Weg funktioniert also auch ohne Empfang).
  `tests/Feature/DrawerPageTest.php` und `tests/Feature/PwaTest.php` wachen
  darüber.
- **Menü einer Zeile (⋮):** Was zu genau einer Zeile gehört, steht hinter dem
  Menü am rechten Rand dieser Zeile – beim Bearbeiten der Schubladen das
  Umbenennen und das Löschen, in der Schublade und in den Listen außerhalb einer
  Schublade (Inventar, MHD-Warnungen) das Bearbeiten, das Löschen und das
  Verschieben eines Artikels. Die Zeile selbst bleibt damit ruhig: sie zeigt
  den Namen und die Zahl der Artikel, und der Knopf des Menüs nennt in seinem
  Namen die Zeile, zu der er gehört („Weitere Aktionen für Schublade 1“, beim
  Artikel „Weitere Aktionen für Erbsen“), damit ein
  Bildschirmleser nicht in jeder Zeile nur „⋮“ liest. Das Menü ist eine
  Komponente (`resources/views/components/menu.blade.php`) und bringt seine
  Gestalt aus `resources/css/app.css` mit (`.menu-panel` und `.menu-item`, im
  dunklen Schema mit eigenen Werten); die Aktionen kommen als Slot, jede mit
  ihrem Symbol und ihrer Beschriftung. Das Panel schwebt über der Liste
  (`z-index: 40`, also über der unteren Leiste der App-Hülle) und schließt mit
  dem Tipp daneben, mit Escape und mit dem Tipp auf eine seiner Aktionen – die
  Aktion selbst ruft die vorhandene Funktion der Seite auf
  (`OpenFreezer::startRenaming()`, `OpenFreezer::deleteDrawer()`; in der
  Schublade `DrawerPage::startEditingItem()` und
  `DrawerPage::startMovingItem()`, und in den Listen draußen führt der Eintrag
  mit dem Artikel in die Schublade, siehe unten), es wird
  also nichts nachgebaut. Gelöscht wird wie überall erst nach der Antwort im
  Dialog der App: das Menü öffnet nur die Rückfrage
  (`components/confirm-dialog.blade.php`), nie den Dialog des Browsers.
  Dasselbe Menü bedient auch das Profilbild: dort kommt statt des „⋮“ ein
  eigener Knopf mit sichtbarer Beschriftung („Profilbild bearbeiten“) über den
  Slot `trigger`, und seine Einträge folgen dem Zustand des Bildes
  (hinzufügen, ändern, löschen).
  `tests/Feature/OpenFreezerTest.php` und `tests/Feature/DrawerPageTest.php`
  wachen über das Menü und seine Aktionen,
  `tests/Feature/DeleteConfirmationTest.php` über die Rückfrage.
- **Die Knöpfe eines Formulars:** Jede Zeile der App, die zwischen „Abbrechen“
  und einer Aktion wählen lässt, endet gleich: „Abbrechen“ (`common.cancel`)
  links, die Hauptaktion rechts – „Speichern“ (`common.save`) beim Ändern,
  „Erstellen“ (`common.create`) beim Anlegen eines Gefrierschranks,
  „Verschieben“ (`drawers.move_submit`) beim Verschieben eines Artikels. Beide
  Knöpfe sind genau gleich
  groß: die Zeile ist ein Gitter aus zwei gleich breiten Spalten
  (`grid grid-cols-2 gap-2`, jeder Knopf `w-full`) und keine Flex-Zeile mit
  `flex-1` auf einem der beiden – dort hinge die Breite an der Länge der
  Beschriftung, und „Abbrechen“ ist länger als „Speichern“. Der Abstand
  zwischen ihnen ist überall derselbe, die Beschriftungen stehen mittig
  (`.btn`), und die Zeile sieht auf dem Telefon aus wie auf dem Rechner: die
  beiden Knöpfe stehen dort nebeneinander, nicht untereinander. Das Paar tragen
  die Formulare der Artikel (`resources/views/livewire/freezer/drawer-page.blade.php`),
  der Gefrierschränke (`closed-freezer.blade.php`) und der Schubladen
  (`open-freezer.blade.php`); hat ein
  Haushalt nur ein einziges Fach, gibt es kein Ziel – dann steht „Abbrechen“
  allein und nimmt die ganze Breite (`col-span-2`). Die Offline-Seite trägt
  dasselbe Paar in ihrem Blatt (`public/offline.html`, `.actions-pair`); die
  Rückfrage vor dem Löschen behält ihre eigene Zeile
  (`components/confirm-dialog.blade.php`) – auf dem Telefon stehen ihre beiden
  Knöpfe untereinander, ab `sm` nebeneinander in umgekehrter Reihenfolge, damit
  die Antwort auch dort rechts steht.
  `tests/Feature/ActionPairTest.php` wacht über diese Ordnung in allen
  Ansichten, `tests/Feature/DrawerPageTest.php` und `tests/Feature/PwaTest.php`
  über die beiden Formulare eines Artikels und das Blatt der Offline-Seite.
- **Artikel verschieben:** Ein Artikel wechselt seinen Platz, ohne neu angelegt zu
  werden – über „Verschieben“ im Menü (⋮) einer Zeile: in der Schublade öffnet es
  das Formular dort, in Inventar und Warnliste führt der Eintrag mit dem Artikel
  in seine Schublade und öffnet es mit „&move=1“ (siehe oben). Zur Wahl
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
- **Die Zeile eines Artikels in der Schublade:** Die Schublade liest ihre Artikel
  mit einer eigenen Zeile
  (`resources/views/components/drawer-item-row.blade.php`): der Name, darunter
  das MHD als Datum mit dem Etikett des Feldes („MHD: 15.12.2026“, also
  `common.best_before`), sonst nichts – weder Gefrierschrank noch Schublade
  stehen noch einmal in der Zeile, der Nutzer ist ja schon dort. Ein Artikel ohne
  MHD sagt es an der Stelle des Datums („Kein MHD“, items.mhd.none).
  Die Karte bleibt weiß – wie jede Zeile der App (die Kategorie eines Artikels
  dient dem Filter des Inventars); die Menge steht rechts mit ihren beiden
  Knöpfen, und daneben öffnet das Menü (⋮) das
  Bearbeiten, das Löschen und das Verschieben dieses Artikels. Die Listen
  außerhalb einer Schublade lesen weiterhin die gemeinsame Zeile
  (`resources/views/components/item-row.blade.php`).
  `tests/Feature/DrawerPageTest.php` wacht darüber.
- **MHD in der Zeile:** Die gemeinsame Zeile eines Artikels
  (`resources/views/components/item-row.blade.php`) nennt das MHD in der zweiten
  Zeile mit dem Etikett des Feldes („MHD: 15.12.2026“, also
  `common.best_before`) und kennt zwei Formen davon. Die stille nennt das Datum
  allein; sie steht in der Warnliste, deren Abschnitte den Zustand schon in der
  Überschrift tragen („Abgelaufen“, „Bald abgelaufen“). Die andere stellt
  daneben, was warnt: das Inventar liest die Zeile mit
  `:mhd-warnings-only="true"` (`InventoryOverview`) und behält den Zustand
  („abgelaufen“, „bald abgelaufen“), solange er warnt – „frisch“ sagt in einer
  Liste nichts, in der das Datum ohnehin daneben steht, und der relative Zähler
  („MHD in 3 Tagen“) steht in keiner Zeile der App.
  `tests/Feature/InventoryOverviewTest.php` und
  `tests/Feature/ExpiringItemsTest.php` wachen über die beiden Formen.
- **Die Zeile der Listen außerhalb einer Schublade:** Inventar und Warnliste
  lesen dieselbe Zeile (`resources/views/components/item-row.blade.php`): der
  Name, darunter das MHD und darunter der Ort – Gefrierschrank und Schublade mit
  Komma. Die Karte ist weiß: die Kategorie eines Artikels dient dem Filter des
  Inventars (`InventoryOverview::filterByCategory()`), sie ist keine Farbe einer
  Zeile. Rechts stehen die Menge mit ihren beiden Knöpfen und das Menü (⋮) mit
  Bearbeiten, Löschen und Verschieben. Bearbeiten und Verschieben sind die
  Funktionen der Schublade, deshalb führt das Menü (⋮) der Zeile mit dem Artikel
  hinein:
  „?item=<id>“ öffnet sein Bearbeiten-Formular, „&move=1“ daneben sein
  Verschieben-Formular (`DrawerPage::openItemFromQuery()`). Der Ort in der
  dritten Zeile ist reiner Text: Gefrierschrank und Schublade stehen in
  derselben Schrift und Farbe mit dem Komma dazwischen, und der Name der
  Schublade ist kein Link – die Zeile lässt sich lesen, ohne dass ein Tipp in
  ihrer Mitte eine Seite öffnet. Das Löschen fragt wie
  überall im Modal der App nach (`components/confirm-dialog.blade.php`).
  `tests/Feature/InventoryOverviewTest.php` und
  `tests/Feature/ExpiringItemsTest.php` wachen über die Zeile,
  `tests/Feature/DrawerPageTest.php` über den Weg über die Adresse.
- **Reihenfolge des Inventars:** Die Liste lässt sich sortieren – nach Name (die
  Voreinstellung), nach Kategorie oder nach dem MHD. Die MHD-Reihenfolge kennt
  nur eine Richtung: „Nach MHD“ stellt oben hin, was zuerst abläuft – die Liste
  beantwortet die Frage, was weg muss, und ein spätestes Datum zuerst bietet sie
  nicht. Die drei Schlüssel stehen in `InventoryItem::SORT_*`, ihre Wörter
  in `lang/<Sprache>/inventory.php` unter `sort` (ein Wort darf kürzer sein als
  sein Schlüssel: „Nach MHD“ ist `best_before_asc`, weil der Schlüssel die Regel
  benennt und der Knopf nur die Reihenfolge), und die drei Felder stehen im
  Filter-Panel
  (`resources/views/livewire/inventory-overview.blade.php`), weil dort schon
  alles versammelt ist, was die Liste verändert. Sie sind alle gleich breit: sie
  stehen in einem Raster aus drei gleich breiten Spalten über die ganze Breite
  des Panels, jedes Feld füllt seine Spalte (`w-full`), stellt sein Etikett
  mittig (`justify-center` und `text-center`) und darf umbrechen
  (`whitespace-normal` – ein Telefon lässt jeder Spalte nur rund 80 Pixel) –
  „Name (A–Z)“ ist damit genau so breit wie „Nach MHD“, unabhängig von der
  Länge des Wortes; eine Reihe flexibler Felder machte jedes so breit wie sein
  Wort. Dieselbe Gestalt tragen die vier Schnellauswahlen des MHD in den
  Formularen (`grid-cols-4` mit `.chip`, siehe oben).
  `InventoryOverview::sortBy()`
  prüft die Wahl gegen `InventoryItem::sortKeys()` – ein unbekannter Schlüssel
  lässt die Liste alphabetisch, genau wie ein unbekanntes Fach „alle
  Gefrierschränke“ bedeutet. Die Abfrage baut `InventoryItem::sortedBy()`: jede
  Reihenfolge endet auf `name_sort` (und der `id`), damit zwei Artikel mit
  derselben Kategorie oder demselben Datum nicht zwischen zwei Aufrufen die
  Plätze tauschen. Die Kategorien folgen der Reihenfolge der Chips
  (`categoryLabels()`); Artikel ohne Kategorie und Artikel ohne MHD schließen
  die Liste – „kein MHD“ ist kein Datum, das dringend werden könnte, und ein
  schlichtes `ORDER BY best_before` stellte die NULLs sonst nach oben (MySQL und
  SQLite gleich).
  Zwei Reihenfolgen gruppieren die Liste: die nach Kategorie und die nach MHD.
  `InventoryOverview::groups()` teilt sie dafür in Abschnitte – eine Gruppe je
  Kategorie in der Reihenfolge der Chips, jede Gruppe für sich auf `name_sort`
  sortiert, eine leere Kategorie ohne Überschrift; mit „Nach MHD“ eine Gruppe je
  Fenster der restlichen Haltbarkeit (`InventoryItem::bestBeforeGroup()`: was
  innerhalb des nächsten Monats abläuft – abgelaufene Artikel eingeschlossen,
  sie sind das Dringendste –, was innerhalb der nächsten sechs Monate, was
  länger hält, und zuletzt „Kein MHD hinterlegt“), jede Gruppe für sich
  aufsteigend nach dem Datum und erst bei gleichem Datum nach `name_sort`
  sortiert, die Artikel ohne Datum alphabetisch unter sich, ein leeres Fenster
  ohne Überschrift –, und die Ansicht rendert beide Formen über denselben Loop:
  `<section>` mit der Überschrift und der Zahl der Artikel daneben, in der
  Gestalt der Abschnitte der MHD-Warnliste; jede andere Reihenfolge bleibt die
  eine, flache Liste. Die Fenster sind Kalendermonate ab heute
  (`addMonthsNoOverflow`, wie die Schnellauswahlen des MHD), ihre Wörter stehen
  in `lang/<Sprache>/inventory.php` unter `best_before_groups`. Ein Artikel ohne
  Kategorie wird als „Sonstiges“ gelesen: die Gruppe entsteht nur beim Lesen – in
  der Datenbank bleibt die Kategorie leer –, und ein Artikel, der später eine
  Kategorie bekommt, steht in deren Gruppe; dasselbe gilt für ein Datum, das ein
  Artikel nachträglich bekommt oder verliert: er steht mit dem nächsten Aufruf in
  seinem Fenster. Das Sortieren der Gruppen hebt die Reihenfolge der Abfrage für
  sie auf: die Artikel ohne Kategorie und die ohne MHD schließen dort die Liste,
  hier stehen sie alphabetisch unter „Sonstiges“ bzw. unter „Kein MHD
  hinterlegt“.
  Sortieren ist kein Filter: `isFiltered` und der Punkt am
  Filter-Icon bleiben davon unberührt, und `clearFilters()` nimmt nur Suche und
  Filter zurück, nicht die Reihenfolge.
  `tests/Feature/InventoryOverviewTest.php`,
  `tests/Feature/LanguageTest.php` und
  `tests/Unit/InventoryItemStateTest.php` wachen darüber.
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
