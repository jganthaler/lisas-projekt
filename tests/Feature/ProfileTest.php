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

    /**
     * A real (1x1) PNG: the "image" rule checks the content of the file, and
     * unlike UploadedFile::fake()->image() this needs no GD extension.
     */
    private function png(string $name): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg==');

        return UploadedFile::fake()->createWithContent($name, $png);
    }

    public function test_the_profile_is_prefilled_with_the_current_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Lisa Muster',
            'email' => 'lisa@example.com',
        ]);

        $this->page($user)
            ->assertSet('name', 'Lisa Muster')
            ->assertSet('email', 'lisa@example.com')
            ->assertSee('Angaben');
    }

    public function test_the_profile_can_be_updated(): void
    {
        $user = User::factory()->create(['name' => 'Alter Name', 'email' => 'alt@example.com']);

        $this->page($user)
            ->set('name', 'Neuer Name')
            ->set('email', 'neu@example.com')
            ->call('updateProfile')
            ->assertHasNoErrors()
            ->assertSet('feedback', 'Profil gespeichert.');

        $user->refresh();

        $this->assertSame('Neuer Name', $user->name);
        $this->assertSame('neu@example.com', $user->email);
    }

    public function test_an_email_address_of_another_user_is_rejected(): void
    {
        User::factory()->create(['email' => 'belegt@example.com']);
        $user = User::factory()->create(['email' => 'frei@example.com']);

        $this->page($user)
            ->set('email', 'belegt@example.com')
            ->call('updateProfile')
            ->assertHasErrors('email');

        $this->assertSame('frei@example.com', $user->refresh()->email);
    }

    public function test_the_profile_cannot_be_emptied(): void
    {
        $user = User::factory()->create();

        $this->page($user)
            ->set('name', '')
            ->set('email', 'keine-mailadresse')
            ->call('updateProfile')
            ->assertHasErrors(['name', 'email']);
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
        // upload of the field stores the image.
        $this->page($user)
            ->assertDontSee('Bild speichern')
            ->set('avatar', $this->png('ich.png'))
            ->assertHasNoErrors()
            ->assertSet('feedback', 'Profilbild gespeichert.')
            ->assertSet('avatar', null);

        $user->refresh();

        $this->assertTrue($user->hasAvatar());
        Storage::disk('local')->assertExists($user->avatar_path);

        $path = $user->avatar_path;

        $this->page($user)
            ->call('removeAvatar')
            ->assertSet('feedback', 'Profilbild entfernt.');

        $this->assertFalse($user->refresh()->hasAvatar());
        Storage::disk('local')->assertMissing($path);
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
