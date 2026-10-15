<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Models\Freezer;
use App\Models\FreezerMembership;
use App\Models\User;
use App\Support\FreezerContext;
use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_the_login_screen(): void
    {
        $this->get(route('freezer.closed'))->assertRedirect(route('login'));
        $this->get(route('inventory.index'))->assertRedirect(route('login'));
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
    }

    public function test_the_login_and_register_screens_can_be_rendered(): void
    {
        $this->get(route('login'))->assertOk();
        $this->get(route('register'))->assertOk();
    }

    /**
     * The auth screens show the word mark of the app instead of the plain name.
     */
    public function test_the_auth_screens_carry_the_word_mark_of_the_app(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSeeHtml('aria-label="Coolventory"')
            ->assertSeeHtml('>cool</span>')
            ->assertSeeHtml('>ventory</span>');
    }

    public function test_users_can_login_with_their_credentials(): void
    {
        $user = User::factory()->create(['password' => 'geheim1234']);

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'geheim1234')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('freezer.closed'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_wrong_password_keeps_the_visitor_guest(): void
    {
        $user = User::factory()->create(['password' => 'geheim1234']);

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'falsch1234')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_registration_provisions_a_freezer_with_three_drawers(): void
    {
        Notification::fake();

        Livewire::test(Register::class)
            ->set('name', 'Lisa Muster')
            ->set('email', 'lisa@example.com')
            ->set('password', 'geheim1234')
            ->set('password_confirmation', 'geheim1234')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('verification.notice'));

        $user = User::query()->firstWhere('email', 'lisa@example.com');

        $this->assertNotNull($user);
        $this->assertAuthenticatedAs($user);

        $freezer = $user->ownedFreezers()->first();

        $this->assertNotNull($freezer);
        $this->assertSame(Freezer::DEFAULT_NAME, $freezer->name);
        $this->assertSame(Freezer::DEFAULT_DRAWER_COUNT, $freezer->drawers()->count());
        $this->assertSame(FreezerMembership::ROLE_OWNER, $user->roleIn($freezer));

        // The new freezer is the active one right away.
        $this->assertSame($freezer->getKey(), FreezerContext::id());

        // The account itself still has to confirm its e-mail address.
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_registration_rejects_invalid_input(): void
    {
        Livewire::test(Register::class)
            ->set('name', '')
            ->set('email', 'keine-mailadresse')
            ->set('password', 'kurz')
            ->set('password_confirmation', 'anders')
            ->call('register')
            ->assertHasErrors(['name', 'email', 'password']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_users_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
