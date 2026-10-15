<?php

namespace Tests\Feature;

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\ResetPassword;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * "Passwort vergessen?" and the page behind the link of the mail.
 *
 * The token belongs to exactly one account, works exactly once and expires,
 * so the link in an old mail cannot be used to take over an account later.
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_login_screen_leads_to_the_request_page(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Passwort vergessen?');

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Passwort vergessen?');
    }

    public function test_a_reset_link_is_mailed_to_the_account(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        Livewire::test(ForgotPassword::class)
            ->set('email', $user->email)
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('sent', true)
            ->assertSee($user->email);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_the_mail_points_at_the_reset_page_of_this_app(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        Livewire::test(ForgotPassword::class)
            ->set('email', $user->email)
            ->call('send');

        Notification::assertSentTo(
            $user,
            ResetPasswordNotification::class,
            function (ResetPasswordNotification $notification) use ($user): bool {
                $url = $notification->toMail($user)->actionUrl;

                // The link carries the one-time token and the address of the
                // account, so the form is already filled in.
                return str_contains($url, route('password.reset', ['token' => $notification->token], false))
                    && str_contains($url, urlencode($user->email));
            },
        );
    }

    public function test_the_mail_is_written_in_german(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        Livewire::test(ForgotPassword::class)
            ->set('email', $user->email)
            ->call('send');

        Notification::assertSentTo(
            $user,
            ResetPasswordNotification::class,
            function (ResetPasswordNotification $notification) use ($user): bool {
                $mail = $notification->toMail($user);

                return $mail->subject === 'Passwort zurücksetzen'
                    && $mail->greeting === 'Hallo '.$user->displayName().'!'
                    && $mail->actionText === 'Neues Passwort wählen';
            },
        );
    }

    public function test_the_answer_does_not_reveal_whether_an_account_exists(): void
    {
        Notification::fake();

        Livewire::test(ForgotPassword::class)
            ->set('email', 'niemand@example.com')
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('sent', true);

        Notification::assertNothingSent();
    }

    public function test_a_second_link_is_not_mailed_right_away(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $page = Livewire::test(ForgotPassword::class)->set('email', $user->email);

        $page->call('send')->assertSet('sent', true);

        // The broker waits a minute before it mails the next link for an account.
        $page->call('send')
            ->assertHasErrors('email')
            ->assertSet('sent', false);

        Notification::assertSentTimes(ResetPasswordNotification::class, 1);
    }

    public function test_requests_without_an_account_are_turned_down_at_some_point(): void
    {
        Notification::fake();

        $page = Livewire::test(ForgotPassword::class)->set('email', 'niemand@example.com');

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $page->call('send')->assertHasNoErrors();
        }

        $page->call('send')->assertHasErrors('email');

        Notification::assertNothingSent();
    }

    public function test_the_reset_page_is_rendered_with_the_address_of_the_link(): void
    {
        $user = User::factory()->create();

        $this->get(route('password.reset', [
            'token' => Password::createToken($user),
            'email' => $user->email,
        ]))
            ->assertOk()
            ->assertSee($user->email);
    }

    public function test_a_new_password_can_be_chosen_with_the_token(): void
    {
        $user = User::factory()->create(['password' => 'altespasswort']);

        Livewire::test(ResetPassword::class, ['token' => Password::createToken($user)])
            ->set('email', $user->email)
            ->set('password', 'neuespasswort')
            ->set('password_confirmation', 'neuespasswort')
            ->call('resetPassword')
            ->assertHasNoErrors()
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('neuespasswort', $user->refresh()->password));

        // The token is used up.
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_the_token_of_another_account_does_not_open_the_page(): void
    {
        $user = User::factory()->create(['password' => 'altespasswort']);
        $other = User::factory()->create(['password' => 'altespasswort']);

        Livewire::test(ResetPassword::class, ['token' => Password::createToken($other)])
            ->set('email', $user->email)
            ->set('password', 'neuespasswort')
            ->set('password_confirmation', 'neuespasswort')
            ->call('resetPassword')
            ->assertHasErrors('email');

        $this->assertTrue(Hash::check('altespasswort', $user->refresh()->password));
    }

    public function test_a_token_cannot_be_used_twice(): void
    {
        $user = User::factory()->create(['password' => 'altespasswort']);

        $page = Livewire::test(ResetPassword::class, ['token' => Password::createToken($user)]);

        $page->set('email', $user->email)
            ->set('password', 'neuespasswort')
            ->set('password_confirmation', 'neuespasswort')
            ->call('resetPassword')
            ->assertHasNoErrors();

        // Opening the old link again does not change anything anymore.
        $page->set('password', 'nochmalneu')
            ->set('password_confirmation', 'nochmalneu')
            ->call('resetPassword')
            ->assertHasErrors('email');

        $this->assertTrue(Hash::check('neuespasswort', $user->refresh()->password));
    }

    public function test_the_new_password_has_to_be_long_enough_and_repeated(): void
    {
        $user = User::factory()->create(['password' => 'altespasswort']);

        Livewire::test(ResetPassword::class, ['token' => Password::createToken($user)])
            ->set('email', $user->email)
            ->set('password', 'kurz')
            ->set('password_confirmation', 'anders')
            ->call('resetPassword')
            ->assertHasErrors(['password']);

        $this->assertTrue(Hash::check('altespasswort', $user->refresh()->password));
    }

    public function test_the_address_is_required(): void
    {
        Livewire::test(ResetPassword::class, ['token' => 'irgendein-token'])
            ->set('email', '')
            ->set('password', 'neuespasswort')
            ->set('password_confirmation', 'neuespasswort')
            ->call('resetPassword')
            ->assertHasErrors(['email']);
    }
}
