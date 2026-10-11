<?php

namespace Tests\Feature;

use App\Livewire\Profile\ProfilePage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

    public function test_an_avatar_can_be_uploaded_and_removed(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('The GD extension is required to fake an uploaded image.');
        }

        Storage::fake('local');

        $user = User::factory()->create();

        $this->page($user)
            ->set('avatar', UploadedFile::fake()->image('ich.jpg', 120, 120))
            ->call('saveAvatar')
            ->assertHasNoErrors()
            ->assertSet('feedback', 'Profilbild gespeichert.');

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

    public function test_a_file_that_is_not_an_image_is_rejected(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $this->page($user)
            ->set('avatar', UploadedFile::fake()->create('notizen.pdf', 20, 'application/pdf'))
            ->call('saveAvatar')
            ->assertHasErrors('avatar');

        $this->assertFalse($user->refresh()->hasAvatar());
    }
}
