<?php

namespace Tests\Feature;

use App\Livewire\Profile\ProfilePage;
use App\Models\Freezer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The installed app: the icon, the manifest and the service worker that makes
 * Coolventory installable and answers a navigation without network.
 *
 * Whoever adds Coolventory to the home screen should get the snowflake and not
 * a screenshot of the browser: one artwork (public/favicon.png) is the source
 * for the tab, the home screen and the PWA manifest, and every layout of the
 * app links it through resources/views/components/app-icons.blade.php.
 */
class PwaTest extends TestCase
{
    use RefreshDatabase;

    /** The brand colour of the app, also used by the theme-color meta tag. */
    private const BRAND = '#0d8280';

    public function test_the_artwork_of_the_icon_exists(): void
    {
        $path = public_path('favicon.png');

        $this->assertFileExists($path);

        $size = getimagesize($path);

        $this->assertIsArray($size);

        // 512 x 512: Chrome asks for at least 192 for an installed app, and the
        // same file is downscaled for the tab of the browser.
        $this->assertSame(512, $size[0], 'Das Icon ist nicht 512 Pixel breit.');
        $this->assertSame(512, $size[1], 'Das Icon ist nicht 512 Pixel hoch.');
        $this->assertSame('image/png', $size['mime']);
    }

    public function test_the_manifest_names_the_app_and_points_at_the_icon(): void
    {
        $manifest = $this->manifest();

        $this->assertSame('Coolventory', $manifest['name']);
        $this->assertSame('Coolventory', $manifest['short_name']);
        $this->assertSame('/', $manifest['start_url']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame(self::BRAND, $manifest['theme_color']);

        $icons = $manifest['icons'];

        $this->assertIsArray($icons);
        $this->assertCount(2, $icons);

        foreach ($icons as $icon) {
            $this->assertSame('/favicon.png', $icon['src']);
            $this->assertSame('512x512', $icon['sizes']);
            $this->assertSame('image/png', $icon['type']);
        }

        // Android cuts the icon of an installed app into the shape of the
        // launcher. The artwork fills its square edge to edge and the snowflake
        // sits well inside the safe circle, so the very same file may be offered
        // as a plain and as a maskable icon.
        $this->assertSame(['any', 'maskable'], array_column($icons, 'purpose'));
    }

    public function test_the_app_frame_links_the_icon_and_the_manifest(): void
    {
        $owner = User::factory()->create();

        Freezer::factory()->withDrawers()->create(['owner_id' => $owner->getKey()]);

        $response = $this->actingAs($owner)->get(route('freezer.closed'))->assertOk();

        $this->assertIconsAreLinked($response);

        // Der Name unter dem Icon des Startbildschirms: neuere Versionen von iOS
        // lesen ihn aus dem Manifest, ältere nur aus dieser Angabe.
        $response->assertSeeHtml('<meta name="apple-mobile-web-app-title" content="Coolventory">');
    }

    /**
     * The sign in screen and the legal pages carry the same head, so a page that
     * was added to the home screen from there gets the icon as well.
     */
    public function test_the_guest_pages_link_the_icon_and_the_manifest(): void
    {
        foreach ([route('login'), route('legal.imprint')] as $url) {
            $this->assertIconsAreLinked($this->get($url)->assertOk());
        }
    }

    /**
     * The worker is what makes the app installable at all: Chrome, Edge and
     * Android only offer the installation of a page that registers a service
     * worker with a fetch handler. Everything it needs while the network is
     * gone has to be in its precache - above all the offline page it answers a
     * navigation with.
     */
    public function test_the_service_worker_keeps_the_offline_page_ready(): void
    {
        $worker = $this->publicFile('sw.js');

        $precache = $this->workerValue($worker, 'PRECACHE_URLS');

        $this->assertIsArray($precache);
        $this->assertSame(['/offline.html', '/favicon.png', '/manifest.json'], $precache);

        foreach ($precache as $url) {
            $this->assertFileExists(
                public_path(ltrim((string) $url, '/')),
                'Die Datei '.$url.' des Vorrats fehlt in public/.'
            );
        }

        // The page that takes the place of a navigation without network is part
        // of the precache - a phone that never saw the network would otherwise
        // find nothing to show.
        $this->assertContains($this->workerValue($worker, 'OFFLINE_URL'), $precache);

        foreach (['install', 'activate', 'fetch'] as $event) {
            $this->assertMatchesRegularExpression(
                '/addEventListener\(\s*[\'"]'.$event.'[\'"]/',
                $worker,
                'Der Service Worker hört nicht auf "'.$event.'".'
            );
        }
    }

    /**
     * The offline page has to stand on its own: the CSS of the app is a built
     * file, the server is gone, and the app frame is nowhere near. So it brings
     * its own styles, its own script and the three languages of the app - in
     * the colours of the palette and under the same keys in the local storage
     * that the app itself writes (see resources/js/app.js and
     * components/theme-script.blade.php).
     */
    public function test_the_offline_page_speaks_every_language(): void
    {
        $page = $this->publicFile('offline.html');

        $this->assertStringContainsString(self::BRAND, $page, 'Die Offline-Seite trägt nicht die Markenfarbe.');
        $this->assertStringContainsString("'app-locale'", $page);
        $this->assertStringContainsString("'color-scheme'", $page);

        $texts = [
            'de' => ['Keine Verbindung', 'Erneut versuchen'],
            'it' => ['Nessuna connessione', 'Riprova'],
            'en' => ['No connection', 'Try again'],
        ];

        foreach ($texts as $locale => $phrases) {
            foreach ($phrases as $phrase) {
                $this->assertStringContainsString(
                    $phrase,
                    $page,
                    'Die Offline-Seite fehlt in der Sprache "'.$locale.'".'
                );
            }
        }
    }

    /**
     * The offline page is not only a note: the cellar has no reception, so it
     * is the inventory itself. It draws the copy that resources/js/app.js keeps
     * in the local storage of the browser and hands its changes to the sync -
     * the three keys below are the contract between the two.
     */
    public function test_the_offline_page_can_work_with_the_inventory(): void
    {
        $page = $this->publicFile('offline.html');

        foreach (['offline-snapshot', 'offline-queue', 'csrf-token'] as $key) {
            $this->assertStringContainsString("'".$key."'", $page, 'Die Offline-Seite kennt "'.$key.'" nicht.');
        }

        $this->assertStringContainsString("'/offline/sync'", $page);

        // Deleting asks through the sheet of the page, never through the dialog
        // of the browser - the same rule as in the app itself (see
        // tests/Feature/DeleteConfirmationTest.php).
        $this->assertStringNotContainsString('window.confirm', $page);
        $this->assertStringContainsString('data-confirm-yes', $page);
    }

    /**
     * Die Offline-Seite lässt die Stelle des Datums so wenig leer wie die App:
     * eine Zeile ohne MHD sagt „Kein MHD“ (mhdLabels.none, siehe
     * public/offline.html, itemRow()). Die Wörter stehen nicht zweimal im
     * Projekt – sie kommen mit dem Schnappschuss (siehe
     * App\Support\OfflineInventory::labels()) –, deshalb ist hier nur die Zeile
     * selbst gewacht.
     */
    public function test_the_offline_row_says_when_there_is_no_mhd(): void
    {
        $page = $this->publicFile('offline.html');

        $this->assertStringContainsString(
            "meta = '<span>' + esc(mhdLabels.none) + '</span>';",
            $page,
            'Die Offline-Zeile lässt die Stelle des Datums leer.'
        );
    }

    /**
     * What the offline page can do, in every language of the app.
     */
    public function test_the_offline_page_carries_the_editor_in_every_language(): void
    {
        $page = $this->publicFile('offline.html');

        $texts = [
            'de' => ['Artikel hinzufügen', 'Jetzt synchronisieren', 'Artikel verschieben', 'Löschen', 'Hinzufügen abbrechen'],
            'it' => ['Aggiungi articolo', 'Sincronizza ora', 'Sposta articolo', 'Elimina', "Annulla l'aggiunta"],
            'en' => ['Add item', 'Sync now', 'Move item', 'Delete', 'Cancel adding'],
        ];

        foreach ($texts as $locale => $phrases) {
            foreach ($phrases as $phrase) {
                $this->assertStringContainsString(
                    $phrase,
                    $page,
                    'Dem Offline-Modus fehlt in der Sprache "'.$locale.'" das Wort "'.$phrase.'".'
                );
            }
        }
    }

    /**
     * The offline page is the inventory itself, so it shows the four quick
     * choices of the MHD the way the app shows them: in four equal columns over
     * the whole width of the field, the label in the middle of every pill
     * (public/offline.html, .chips and .chip - the app carries the same shape in
     * resources/css/app.css). No view test reaches the static page, so its shape
     * is guarded here.
     */
    public function test_the_offline_page_offers_the_quick_choices_in_four_equal_columns(): void
    {
        $page = $this->publicFile('offline.html');

        $this->assertMatchesRegularExpression(
            '/\.chips\s*\{[^}]*display:\s*grid;[^}]*repeat\(4,\s*minmax\(0,\s*1fr\)\)/s',
            $page,
            'Die vier Schnellauswahlen des MHD stehen offline nicht in vier gleich breiten Spalten.'
        );

        // The pill fills its column and puts its label in the middle of it.
        $this->assertMatchesRegularExpression(
            '/\.chip\s*\{[^}]*width:\s*100%;/s',
            $page,
            'Die Pille der Schnellauswahl füllt offline nicht ihre Spalte.'
        );

        $this->assertMatchesRegularExpression(
            '/\.chip\s*\{[^}]*text-align:\s*center;/s',
            $page,
            'Das Etikett der Schnellauswahl steht offline nicht mittig.'
        );
    }

    /**
     * The sheet of an item - changing it or moving it - carries the same pair
     * of buttons as the form of the app: "Abbrechen" left, the action right,
     * and both of exactly the same size. The app does it with two equal columns
     * of a grid (resources/views/livewire/freezer/drawer-page.blade.php,
     * "grid grid-cols-2"), the offline page with ".actions-pair" - the order in
     * the markup is the order on the screen. No view test reaches the static
     * page, so its shape is guarded here.
     */
    public function test_the_offline_sheet_puts_cancel_before_the_action_in_two_equal_columns(): void
    {
        $page = $this->publicFile('offline.html');

        $this->assertMatchesRegularExpression(
            '/<div class="actions actions-pair">\s*'
                .'<button type="button" class="btn btn-ghost" data-sheet-cancel[^>]*>[^<]*<\/button>\s*'
                .'<button type="submit" class="btn btn-primary" data-sheet-submit>/s',
            $page,
            'Das Offline-Blatt stellt „Abbrechen“ nicht vor die Aktion.'
        );

        $this->assertMatchesRegularExpression(
            '/\.actions\.actions-pair\s*\{[^}]*display:\s*grid;[^}]*'
                .'grid-template-columns:\s*repeat\(2,\s*minmax\(0,\s*1fr\)\)/s',
            $page,
            'Die beiden Knöpfe des Offline-Blatts teilen die Zeile nicht in zwei gleich breite Spalten.'
        );
    }

    /**
     * The drawer of the offline page appears the way the drawer of the app
     * appears: with its contents, not with the form for a new item. The form
     * waits behind the plus next to the name of the drawer (data-add hidden,
     * data-action="add-toggle") and comes up on a tap - and the plus then is the
     * cross that closes it again.
     */
    public function test_the_offline_drawer_appears_without_the_form_for_a_new_item(): void
    {
        $page = $this->publicFile('offline.html');

        $this->assertMatchesRegularExpression(
            '/<form class="card stack" data-add hidden>/',
            $page,
            'Das Formular der Offline-Seite steht offen, statt hinter dem Plus zu warten.'
        );

        $this->assertStringContainsString('data-action="add-toggle"', $page);
        $this->assertStringContainsString('data-icon="plus"', $page);
        $this->assertStringContainsString("action === 'add-toggle'", $page);

        // The plus becomes the cross of the form while it stands open, so the
        // offline page knows that icon as well (see the icon set of the app,
        // resources/views/components/icon.blade.php).
        $this->assertStringContainsString("'close': '<path d=\"M6 6l12 12\"/>", $page);
    }

    /**
     * The form for a new item is the whole drawer of the offline page while it
     * stands open - exactly as in the app (drawer-page.blade.php): the heading
     * "Inhalt" and the list of the drawer step aside, so nothing below the field
     * leads away from the item that is being entered.
     */
    public function test_the_offline_drawer_gives_way_to_the_form_for_a_new_item(): void
    {
        $page = $this->publicFile('offline.html');

        $this->assertMatchesRegularExpression(
            '/<h2 class="section" data-text="contents" data-contents>/',
            $page,
            'Die Überschrift "Inhalt" der Offline-Schublade ist nicht ansprechbar.'
        );

        $this->assertStringContainsString("contents: document.querySelector('[data-contents]')", $page);
        $this->assertStringContainsString('show(elements.contents, !addingItem);', $page);
        $this->assertStringContainsString('show(elements.items, !addingItem);', $page);
    }

    /**
     * Der Weg zurück aus dem Offline-Fach führt auf die Startseite des
     * Gefrierschranks - genau wie in der App (drawer-page.blade.php): der Pfeil
     * verspricht „Zurück zum Gefrierschrank“ (drawers.back_aria), und die
     * Schublade gehört zum Reiter „Gefrierschrank“ der Fußleiste. Ohne Netz
     * zeigt die Adresse /freezer das Inventar, der Weg funktioniert also auch
     * ohne Empfang.
     */
    public function test_the_offline_drawer_leads_back_to_the_start_page(): void
    {
        $page = $this->publicFile('offline.html');

        $this->assertStringContainsString(
            '<a class="icon-btn" href="/freezer" data-icon="arrow-left" data-aria="back"></a>',
            $page,
            'Der Pfeil des Offline-Fachs führt nicht auf die Startseite des Gefrierschranks.'
        );

        $this->assertStringNotContainsString('href="/freezer/open"', $page);
    }

    /**
     * The copy of the inventory belongs to the account that filled it: the sign
     * in screens say so with a meta tag, and the script of the app drops it.
     */
    public function test_the_sign_in_screen_drops_the_copy_of_the_inventory(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSeeHtml('<meta name="offline-clear" content="1">');

        $owner = User::factory()->create();

        Freezer::factory()->withDrawers()->create(['owner_id' => $owner->getKey()]);

        // A page of the app never carries the tag - nobody loses the copy of
        // the inventory by walking through the app.
        $this->actingAs($owner)
            ->get(route('freezer.closed'))
            ->assertOk()
            ->assertDontSeeHtml('offline-clear');
    }

    /**
     * The note in the app frame is the way into the offline mode: the script of
     * the app reveals it as soon as the device reports that it has no network,
     * and the link leads to the offline page itself - a plain link, because the
     * page change has to pass the server.
     */
    public function test_the_app_frame_leads_into_the_offline_mode(): void
    {
        $owner = User::factory()->create();

        Freezer::factory()->withDrawers()->create(['owner_id' => $owner->getKey()]);

        $this->actingAs($owner)
            ->get(route('freezer.closed'))
            ->assertOk()
            ->assertSeeHtml('data-offline-notice')
            ->assertSee('Keine Verbindung – dein Inventar kannst du trotzdem bearbeiten.')
            ->assertSeeHtml('href="/offline.html"');

        // The note follows the language of the account, like every other text.
        $italian = User::factory()->create(['locale' => 'it']);

        $this->actingAs($italian)
            ->get(route('freezer.closed'))
            ->assertOk()
            ->assertSee('Apri la modalità offline');
    }

    /**
     * The script of the app is what fills the copy and sends it: it reads the
     * snapshot, keeps the queue and the token for the offline page, and tells
     * the screens of the app when the changes arrived.
     */
    public function test_the_app_fills_the_copy_for_the_offline_page(): void
    {
        $script = (string) file_get_contents(resource_path('js/app.js'));

        $needles = [
            "'/offline/snapshot'",
            "'/offline/sync'",
            "'offline-snapshot'",
            "'offline-queue'",
            "'csrf-token'",
            "'offline-synced'",
        ];

        foreach ($needles as $needle) {
            $this->assertStringContainsString($needle, $script, 'Dem Skript der App fehlt '.$needle.'.');
        }
    }

    /**
     * The worker is registered from the one script every layout loads - and
     * "updateViaCache: none" keeps a deployment from waiting for the HTTP cache
     * of the browser before a new worker is seen.
     */
    public function test_the_app_registers_the_service_worker(): void
    {
        $script = (string) file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString("navigator.serviceWorker.register('/sw.js'", $script);
        $this->assertStringContainsString("updateViaCache: 'none'", $script);

        // The offer of the browser is the one way to install the app from
        // inside it; the profile section waits for it.
        $this->assertStringContainsString('beforeinstallprompt', $script);
    }

    /**
     * The installation is offered where the account is: the section stays
     * hidden until the script of the app reveals it, so it never bothers
     * anybody on a desktop browser or inside an installed app.
     */
    public function test_the_profile_offers_to_install_the_app(): void
    {
        $owner = User::factory()->create();

        Livewire::actingAs($owner)->test(ProfilePage::class)
            ->assertSee('App installieren')
            ->assertSee('Zum Startbildschirm hinzufügen')
            ->assertSee('Tippe unten auf „Teilen“')
            ->assertSeeHtml('data-install-section')
            ->assertSeeHtml('data-install-ios')
            ->assertSeeHtml('data-install-button');

        // The texts of the section follow the language of the account, like
        // every other text of the app.
        $italian = User::factory()->create(['locale' => 'it']);

        $this->actingAs($italian)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee("Installa l'app")
            ->assertSee('Aggiungi alla schermata Home');
    }

    /**
     * @return array<string, mixed>
     */
    private function manifest(): array
    {
        $path = public_path('manifest.json');

        $this->assertFileExists($path, 'Das Manifest der App fehlt.');

        $manifest = json_decode((string) file_get_contents($path), true);

        $this->assertIsArray($manifest, 'Das Manifest ist kein gültiges JSON.');

        return $manifest;
    }

    private function assertIconsAreLinked(TestResponse $response): void
    {
        $icon = asset('favicon.png');

        $response
            ->assertSeeHtml('<link rel="icon" type="image/png" sizes="512x512" href="'.$icon.'">')
            ->assertSeeHtml('<link rel="apple-touch-icon" sizes="512x512" href="'.$icon.'">')
            ->assertSeeHtml('<link rel="manifest" href="'.asset('manifest.json').'">');
    }

    /**
     * A file of public/ as text. The test client only knows the routes of the
     * app, so the worker and the offline page are read from the disk - the same
     * way the manifest above is read.
     */
    private function publicFile(string $name): string
    {
        $path = public_path($name);

        $this->assertFileExists($path, 'Die Datei public/'.$name.' fehlt.');

        return (string) file_get_contents($path);
    }

    /**
     * A value of the service worker, read out of the file: the worker writes
     * its constants with double quotes, so the list of the precache can be
     * decoded as JSON. The test therefore checks the list itself instead of
     * repeating it - a file that is added to the worker cannot be forgotten
     * here.
     */
    private function workerValue(string $worker, string $name): mixed
    {
        $this->assertMatchesRegularExpression(
            '/const '.$name.' = (.+?);/',
            $worker,
            'Der Service Worker kennt "'.$name.'" nicht.'
        );

        preg_match('/const '.$name.' = (.+?);/', $worker, $matches);

        $value = json_decode($matches[1], true);

        $this->assertNotNull($value, 'Der Wert "'.$name.'" des Service Workers ist nicht lesbar.');

        return $value;
    }
}
