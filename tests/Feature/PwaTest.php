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
