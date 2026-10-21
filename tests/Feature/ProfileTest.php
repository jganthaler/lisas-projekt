<?php

namespace Tests\Feature;

use App\Livewire\Profile\ProfilePage;
use App\Models\Freezer;
use App\Models\FreezerMembership;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return Testable<ProfilePage>
     */
    private function page(User $user)
    {
        return Livewire::actingAs($user)->test(ProfilePage::class);
    }

    /** The bytes of a real (1x1) PNG - see png(). */
    private function pngBytes(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg==');
    }

    /**
     * A real (1x1) PNG: the "image" rule checks the content of the file, and
     * unlike UploadedFile::fake()->image() this needs no GD extension.
     */
    private function png(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $this->pngBytes());
    }

    /**
     * A photo the way a camera produces it - stripes, so that the file really
     * holds a few hundred kilobytes. Needs the GD extension; the tests that
     * use it say so (see App\Support\AvatarImage).
     */
    private function jpeg(string $name, int $width, int $height): UploadedFile
    {
        $image = imagecreatetruecolor($width, $height);

        for ($x = 0; $x < $width; $x += 8) {
            $colour = imagecolorallocate($image, $x % 256, ($x * 5) % 256, ($x * 11) % 256);
            imagefilledrectangle($image, $x, 0, $x + 7, $height, $colour);
        }

        ob_start();
        imagejpeg($image, null, 95);
        $contents = (string) ob_get_clean();

        return UploadedFile::fake()->createWithContent($name, $contents);
    }

    /**
     * A picture with transparent corners, the way a cut-out photo arrives: The
     * corners have to stay transparent, otherwise they would sit in the round
     * avatar as dark edges.
     */
    private function transparentPng(string $name, int $size): UploadedFile
    {
        $image = imagecreatetruecolor($size, $size);

        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));

        imagealphablending($image, true);
        imagefilledellipse($image, (int) ($size / 2), (int) ($size / 2), (int) ($size / 1.5), (int) ($size / 1.5), imagecolorallocate($image, 200, 60, 60));

        ob_start();
        imagepng($image);
        $contents = (string) ob_get_clean();

        return UploadedFile::fake()->createWithContent($name, $contents);
    }

    public function test_the_profile_is_prefilled_with_the_current_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Lisa Muster',
            'email' => 'lisa@example.com',
        ]);

        $this->page($user)
            ->assertSet('name', 'Lisa Muster')
            ->assertSee('lisa@example.com')
            ->assertSee('Angaben');
    }

    /**
     * Der Name lässt sich ändern, die bestätigte E-Mail-Adresse nicht: Sie ist
     * der Name des Kontos und der Empfänger der Links zum Zurücksetzen.
     */
    public function test_the_profile_updates_the_name_and_keeps_the_email_address(): void
    {
        $user = User::factory()->create(['name' => 'Alter Name', 'email' => 'alt@example.com']);

        $this->page($user)
            ->set('name', 'Neuer Name')
            ->call('updateProfile')
            ->assertHasNoErrors()
            ->assertSet('feedback', 'Profil gespeichert.');

        $user->refresh();

        $this->assertSame('Neuer Name', $user->name);
        $this->assertSame('alt@example.com', $user->email);
    }

    /**
     * Die bestätigte Adresse ist nicht mehr änderbar: Das Profil zeigt sie nur
     * an, ein Feld dafür gibt es nicht. Auch eine Anfrage, die eine andere
     * Adresse mitschickt, ändert nichts - die Komponente kennt die Adresse gar
     * nicht mehr, geschrieben wird allein der Name.
     */
    public function test_the_verified_email_address_cannot_be_changed(): void
    {
        User::factory()->create(['email' => 'belegt@example.com']);
        $user = User::factory()->create(['email' => 'frei@example.com']);

        $this->page($user)
            ->assertSee('frei@example.com')
            ->assertSee('Deine Adresse ist bestätigt und kann deshalb nicht geändert werden.')
            ->assertDontSee('wire:model="email"', false)
            ->set('name', 'Neuer Name')
            ->call('updateProfile')
            ->assertHasNoErrors();

        $this->assertSame('frei@example.com', $user->refresh()->email);
    }

    /**
     * Das Häkchen der bestätigten Adresse steht rechts davon: Die Adresse soll
     * links beginnen wie in einem Feld, das man ausfüllen kann.
     */
    public function test_the_verified_email_address_carries_the_check_on_the_right(): void
    {
        $user = User::factory()->create(['email' => 'lisa@example.com']);

        $this->page($user)
            ->assertSeeHtml('input flex items-center justify-between gap-2')
            ->assertSeeHtmlInOrder([
                'lisa@example.com',
                'h-4 w-4 shrink-0 text-brand-600 dark:text-brand-400',
            ]);
    }

    public function test_the_profile_cannot_be_emptied(): void
    {
        $user = User::factory()->create();

        $this->page($user)
            ->set('name', '')
            ->call('updateProfile')
            ->assertHasErrors('name');
    }

    public function test_the_password_can_be_changed(): void
    {
        $user = User::factory()->create(['password' => 'altespasswort']);

        $this->page($user)
            ->set('currentPassword', 'altespasswort')
            ->set('newPassword', 'neuespasswort')
            ->set('newPassword_confirmation', 'neuespasswort')
            ->call('updatePassword')
            ->assertHasNoErrors()
            ->assertSet('feedback', 'Passwort geändert.')
            ->assertSet('currentPassword', '')
            ->assertSet('newPassword', '');

        $this->assertTrue(Hash::check('neuespasswort', $user->refresh()->password));
    }

    public function test_the_current_password_must_match(): void
    {
        $user = User::factory()->create(['password' => 'altespasswort']);

        $this->page($user)
            ->set('currentPassword', 'falsch')
            ->set('newPassword', 'neuespasswort')
            ->set('newPassword_confirmation', 'neuespasswort')
            ->call('updatePassword')
            ->assertHasErrors('currentPassword');

        $this->assertTrue(Hash::check('altespasswort', $user->refresh()->password));
    }

    public function test_the_new_password_must_be_confirmed(): void
    {
        $user = User::factory()->create(['password' => 'altespasswort']);

        $this->page($user)
            ->set('currentPassword', 'altespasswort')
            ->set('newPassword', 'neuespasswort')
            ->set('newPassword_confirmation', 'anders')
            ->call('updatePassword')
            ->assertHasErrors('newPassword');
    }

    public function test_a_chosen_picture_is_stored_right_away(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        // Choosing the file is the whole action: there is no save button, the
        // upload of the field stores the image. Danach wird die Seite geladen,
        // damit auch der Avatar der Kopfzeile das neue Bild zeigt (siehe
        // ProfilePage::updatedAvatar()).
        $this->page($user)
            ->assertDontSee('Bild speichern')
            ->set('avatar', $this->png('ich.png'))
            ->assertHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $this->assertSame('Profilbild gespeichert.', session('status'));

        $user->refresh();

        $this->assertTrue($user->hasAvatar());
        Storage::disk('local')->assertExists($user->avatar_path);

        $path = $user->avatar_path;

        $this->page($user)
            ->call('removeAvatar')
            ->assertRedirect(route('profile.edit'));

        $this->assertSame('Profilbild entfernt.', session('status'));

        $this->assertFalse($user->refresh()->hasAvatar());
        Storage::disk('local')->assertMissing($path);
    }

    /**
     * Die Bedienung des Profilbilds: ein Knopf („Profilbild bearbeiten“) öffnet
     * das Menü der App (components/menu.blade.php), und seine Einträge folgen dem
     * Zustand - hinzufügen ohne Bild, ändern und löschen mit Bild. Die beiden
     * alten Knöpfe („Bild auswählen“ und „Entfernen“) gibt es nicht mehr.
     */
    public function test_the_picture_is_operated_through_one_menu(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $this->page($user)
            // Der Knopf steht unabhängig davon, ob ein Bild da ist ...
            ->assertSee('Profilbild bearbeiten')
            // ... und das Menü bietet ohne Bild allein das Hinzufügen an.
            ->assertSee('Profilbild hinzufügen')
            ->assertDontSee('Profilbild ändern')
            ->assertDontSee('Profilbild löschen')
            // Das Feld für die Auswahl vom Gerät bleibt dasselbe.
            ->assertSeeHtml('wire:model="avatar"');

        $this->page($user)->set('avatar', $this->png('ich.png'))->assertHasNoErrors();

        $this->page($user->refresh())
            ->assertSee('Profilbild bearbeiten')
            ->assertSee('Profilbild ändern')
            ->assertSee('Profilbild löschen')
            ->assertDontSee('Profilbild hinzufügen');
    }

    /**
     * Der Eintrag „Profilbild löschen“ löscht nichts von sich aus: er öffnet die
     * Rückfrage der App (components/confirm-dialog.blade.php), und erst deren
     * Antwort ruft removeAvatar() auf - wie überall in der App (siehe
     * tests/Feature/DeleteConfirmationTest.php).
     */
    public function test_the_delete_entry_of_the_picture_asks_the_dialog_of_the_app(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $this->page($user)->set('avatar', $this->png('ich.png'));

        $page = $this->page($user->refresh());

        $page->assertSeeHtml("wire:click=\"\$dispatch('confirm-dialog', { name: 'avatar-remove' })\"")
            ->assertSee('Profilbild entfernen?')
            ->assertSee('avatar-remove');

        // Der Eintrag selbst hat nichts gelöscht.
        $this->assertTrue($user->refresh()->hasAvatar());

        $path = $user->avatar_path;

        // Erst die Antwort im Dialog der App.
        $page->call('removeAvatar')->assertRedirect(route('profile.edit'));

        $this->assertSame('Profilbild entfernt.', session('status'));

        $this->assertFalse($user->refresh()->hasAvatar());
        Storage::disk('local')->assertMissing($path);
    }

    /**
     * Das Bild links folgt dem Menü von selbst: nach dem Hinzufügen steht dort
     * das gespeicherte Bild (über /avatars/{user}), nach dem Löschen wieder die
     * Initialen - beides über die geladene Seite, wie der Avatar der Kopfzeile.
     *
     * Der Kreis mit den Initialen bleibt dabei unter dem Bild liegen: Er ist der
     * Rückfall, wenn das Bild nicht geladen werden kann (siehe
     * resources/views/components/avatar.blade.php).
     */
    public function test_the_picture_on_the_left_follows_the_menu(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['name' => 'Lisa Muster']);

        // Ohne Bild: die Initialen, kein Bild.
        $this->page($user)
            ->assertSee('LM')
            ->assertDontSeeHtml('data-avatar-fallback class="absolute inset-0 rounded-full bg-brand-600" hidden')
            ->assertDontSeeHtml('/avatars/'.$user->getKey());

        $this->page($user)->set('avatar', $this->png('ich.png'));

        // Mit Bild: es steht an derselben Stelle links, die Initialen warten
        // verborgen darunter - sie treten hervor, wenn das Bild nicht geladen
        // werden kann (siehe resources/views/components/avatar.blade.php).
        $this->page($user->refresh())
            ->assertSeeHtml('data-avatar-fallback class="absolute inset-0 rounded-full bg-brand-600" hidden')
            ->assertSeeHtml('/avatars/'.$user->getKey())
            ->assertSeeHtml('object-cover');

        $this->page($user)->call('removeAvatar');

        // Nach dem Löschen: wieder die Initialen.
        $this->page($user->refresh())
            ->assertSee('LM')
            ->assertDontSeeHtml('data-avatar-fallback class="absolute inset-0 rounded-full bg-brand-600" hidden')
            ->assertDontSeeHtml('/avatars/'.$user->getKey());
    }

    /**
     * Der Avatar der Kopfzeile liegt im Layout außerhalb der Komponente: Ohne das
     * Laden der Seite bliebe er beim alten Zustand stehen. Darum holt jedes Gerät
     * die Seite vom Server - dort steht die Adresse des gespeicherten Bildes mit
     * der Version des Pfades -, und nach dem Entfernen stehen überall wieder die
     * Initialen. Derselbe Weg versorgt ein zweites Gerät: Es liest dasselbe Konto.
     */
    public function test_the_app_frame_shows_the_picture_of_the_account(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['name' => 'Lisa Muster']);

        // Ohne Bild trägt die Kopfzeile die Initialen.
        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('LM')
            ->assertDontSeeHtml('/avatars/'.$user->getKey());

        $this->page($user)->set('avatar', $this->png('ich.png'));

        // Mit Bild steht die Adresse des gespeicherten Bildes in der Seite ...
        $this->get(route('profile.edit'))
            ->assertOk()
            ->assertSeeHtml('/avatars/'.$user->getKey().'?v=')
            ->assertSeeHtml('object-cover');

        $this->page($user)->call('removeAvatar');

        // ... und nach dem Entfernen wieder die Initialen.
        $this->get(route('profile.edit'))
            ->assertOk()
            ->assertDontSeeHtml('/avatars/'.$user->getKey())
            ->assertSee('LM');
    }

    public function test_a_new_picture_replaces_the_old_one(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $this->page($user)->set('avatar', $this->png('erst.png'));

        $first = $user->refresh()->avatar_path;

        $this->page($user)->set('avatar', $this->png('dann.png'));

        $second = $user->refresh()->avatar_path;

        $this->assertNotNull($first);
        $this->assertNotSame($first, $second);
        Storage::disk('local')->assertMissing($first);
        Storage::disk('local')->assertExists($second);
    }

    public function test_the_avatar_url_changes_with_the_picture(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $this->assertNull($user->avatarUrl());

        $this->page($user)->set('avatar', $this->png('erst.png'));

        $first = (string) $user->refresh()->avatarUrl();

        $this->page($user)->set('avatar', $this->png('dann.png'));

        $second = (string) $user->refresh()->avatarUrl();

        $this->assertStringContainsString('/avatars/'.$user->getKey(), $first);

        // Without the version in the URL the browser would keep showing the old
        // picture: the response of /avatars/{user} is cached for an hour.
        $this->assertNotSame($first, $second);
    }

    public function test_a_file_that_is_not_an_image_is_rejected(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $this->page($user)
            ->set('avatar', UploadedFile::fake()->create('notizen.pdf', 20, 'application/pdf'))
            ->assertHasErrors('avatar');

        $this->assertFalse($user->refresh()->hasAvatar());
    }

    /**
     * Ein Bild, das schon klein genug ist, wird nicht angefasst: Neu-Kodieren
     * würde nur Qualität kosten.
     */
    public function test_a_picture_that_already_fits_is_stored_unchanged(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $this->page($user)->set('avatar', $this->png('klein.png'))->assertHasNoErrors();

        $path = $user->refresh()->avatar_path;

        $this->assertStringEndsWith('.png', $path);
        $this->assertSame($this->pngBytes(), Storage::disk('local')->get($path));
    }

    /**
     * Ein Foto vom Handy: 1600 x 1200 Pixel und ein paar hundert Kilobyte. Der
     * Avatar im Kopf ist 36 Pixel breit, das Profil zeigt ihn mit 64 - das Bild
     * wird deshalb vor dem Speichern auf die Kantenlänge des Avatars gebracht.
     */
    #[RequiresPhpExtension('gd')]
    public function test_a_large_picture_is_shrunk_before_it_is_stored(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $upload = $this->jpeg('handy.jpg', 1600, 1200);

        $original = (string) file_get_contents((string) $upload->getRealPath());

        $this->page($user)
            ->set('avatar', $upload)
            ->assertHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $path = $user->refresh()->avatar_path;

        Storage::disk('local')->assertExists($path);

        // Ein JPEG bleibt ein JPEG - nur eben kleiner.
        $this->assertStringEndsWith('.jpg', $path);

        $stored = (string) Storage::disk('local')->get($path);
        $size = getimagesizefromstring($stored);

        $this->assertSame([512, 384], [$size[0], $size[1]]);
        $this->assertLessThan(strlen($original), strlen($stored));
    }

    /**
     * Ein freigestelltes Bild bleibt durchsichtig: Sonst säßen die Ecken im
     * runden Avatar als schwarze Flecken.
     */
    #[RequiresPhpExtension('gd')]
    public function test_a_transparent_picture_keeps_its_transparency(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $this->page($user)
            ->set('avatar', $this->transparentPng('frei.png', 900))
            ->assertHasNoErrors();

        $path = $user->refresh()->avatar_path;

        $this->assertStringEndsWith('.png', $path);

        $stored = (string) Storage::disk('local')->get($path);

        $this->assertSame(512, getimagesizefromstring($stored)[0]);

        $image = imagecreatefromstring($stored);

        // 127 ist die volle Durchsichtigkeit (0 wäre deckend).
        $this->assertSame(127, (imagecolorat($image, 0, 0) >> 24) & 0x7F);
    }

    /**
     * Die Grenze für den Upload liegt bei 10 MB. Darüber hilft auch das
     * Verkleinern nicht mehr - dann kommt eine verständliche Meldung statt der
     * Regel von Livewire.
     */
    public function test_a_picture_beyond_the_upload_limit_is_rejected(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        // Ein echtes PNG, das mit Nullen aufgefüllt ist: Die Prüfung der Größe
        // greift, das Bild selbst bleibt lesbar.
        $upload = UploadedFile::fake()->createWithContent(
            'riesig.png',
            $this->pngBytes().str_repeat("\0", 11 * 1024 * 1024)
        );

        $this->page($user)
            ->set('avatar', $upload)
            ->assertHasErrors(['avatar' => 'max'])
            ->assertSee('Das Bild ist größer als 10 MB');

        $this->assertFalse($user->refresh()->hasAvatar());
    }

    /**
     * Ein GIF (oder ein HEIC vom iPhone) nimmt die App nicht an: Das Bild
     * ließe sich nicht verkleinern und im Browser nicht anzeigen. Das
     * accept-Attribut des Feldes sorgt dafür, dass die Auswahl erst gar nicht
     * angeboten wird.
     */
    public function test_a_picture_in_a_foreign_format_is_rejected(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        // Ein echtes (1x1) GIF - die "image"-Regel von Laravel ließe es durch.
        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        $this->page($user)
            ->set('avatar', UploadedFile::fake()->createWithContent('tanz.gif', $gif))
            ->assertHasErrors(['avatar' => 'mimes'])
            ->assertSee('Bitte wähle ein Bild im Format JPG, PNG oder WebP');

        $this->assertFalse($user->refresh()->hasAvatar());
    }

    /**
     * Das Konto löschen (Art. 17 DSGVO): Mit dem Konto gehen seine eigenen
     * Gefrierschränke samt Inhalt, das Profilbild, die Sitzungen und ein offener
     * Link zum Zurücksetzen des Passworts.
     */
    public function test_the_account_can_be_deleted(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['email' => 'lisa@example.com']);
        $freezer = Freezer::factory()->withDrawers()->create(['owner_id' => $user->getKey()]);
        $drawer = $freezer->drawers()->firstOrFail();
        $item = InventoryItem::factory()->create(['drawer_id' => $drawer->getKey()]);

        $this->page($user)->set('avatar', $this->png('ich.png'));

        $path = $user->refresh()->avatar_path;

        DB::table('password_reset_tokens')->insert([
            'email' => 'lisa@example.com',
            'token' => 'hash-des-links',
            'created_at' => now(),
        ]);

        DB::table('sessions')->insert([
            'id' => 'alte-sitzung',
            'user_id' => $user->getKey(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test',
            'payload' => 'x',
            'last_activity' => now()->getTimestamp(),
        ]);

        $this->page($user)
            ->set('deletePassword', 'password')
            ->call('deleteAccount')
            ->assertHasNoErrors()
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $user->getKey()]);
        $this->assertDatabaseMissing('freezers', ['id' => $freezer->getKey()]);
        $this->assertDatabaseMissing('inventory_items', ['id' => $item->getKey()]);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'lisa@example.com']);
        $this->assertDatabaseMissing('sessions', ['id' => 'alte-sitzung']);
        Storage::disk('local')->assertMissing($path);
    }

    /**
     * Geteilte Gefrierschränke anderer bleiben bestehen: Dort verschwindet nur
     * die Mitgliedschaft der Person, die ihr Konto löscht.
     */
    public function test_deleting_the_account_keeps_the_freezers_of_other_people(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $shared = Freezer::factory()->withDrawers(1)->create(['owner_id' => $owner->getKey()]);

        $shared->memberships()->create([
            'user_id' => $member->getKey(),
            'role' => FreezerMembership::ROLE_MEMBER,
        ]);

        $item = InventoryItem::factory()->create(['drawer_id' => $shared->drawers()->firstOrFail()->getKey()]);

        $this->page($member)
            ->set('deletePassword', 'password')
            ->call('deleteAccount')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('users', ['id' => $member->getKey()]);
        $this->assertDatabaseMissing('freezer_memberships', ['user_id' => $member->getKey()]);
        $this->assertDatabaseHas('freezers', ['id' => $shared->getKey()]);
        $this->assertDatabaseHas('inventory_items', ['id' => $item->getKey()]);
    }

    public function test_the_account_survives_a_wrong_password(): void
    {
        $user = User::factory()->create(['password' => 'altespasswort']);

        $this->page($user)
            ->set('deletePassword', 'falsch')
            ->call('deleteAccount')
            ->assertHasErrors('deletePassword');

        $this->assertDatabaseHas('users', ['id' => $user->getKey()]);
    }

    public function test_the_account_survives_without_a_password(): void
    {
        $user = User::factory()->create();

        $this->page($user)
            ->call('deleteAccount')
            ->assertHasErrors('deletePassword');

        $this->assertDatabaseHas('users', ['id' => $user->getKey()]);
    }

    /**
     * Die Löschung liegt im Profil hinter der Rückfrage der App: Der Knopf
     * selbst löscht nichts, er öffnet nur den Dialog.
     */
    public function test_the_profile_offers_the_deletion_behind_a_confirmation(): void
    {
        $user = User::factory()->create();

        $this->page($user)
            ->assertSee('Konto endgültig löschen')
            ->assertSee('Konto wirklich löschen?')
            ->assertSee('account-delete');
    }

    /**
     * Das Abmelden steht unter dem Löschen des Kontos: Der letzte Knopf der
     * Seite soll nicht der sein, der das Konto endgültig entfernt.
     */
    public function test_the_sign_out_sits_below_the_deletion(): void
    {
        $user = User::factory()->create();

        $this->page($user)
            ->assertSeeInOrder(['Rechtliches', 'Konto löschen', 'Abmelden']);
    }
}
