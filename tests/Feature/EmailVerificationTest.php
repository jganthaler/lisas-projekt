<?php

namespace Tests\Feature;

use App\Livewire\Auth\VerifyEmail;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A new account has to confirm its e-mail address before the freezer opens.
 * The link is signed and bound to the account, so it cannot be guessed, reused
 * for somebody else or replayed for another user.
 */
class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function unverified(): User
    {
        return User::factory()->unverified()->create();
    }

    /**
     * The link exactly as the verification mail builds it.
     */
    private function signedLink(User $user): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->getKey(),
            'hash' => sha1($user->email),
        ]);
    }

    public function test_an_unverified_account_cannot_reach_the_app(): void
    {
        $this->actingAs($this->unverified());

        foreach ([
            route('freezer.closed'),
            route('freezer.open'),
            route('inventory.index'),
            route('expiring.index'),
            route('sharing.index'),
            route('profile.edit'),
        ] as $url) {
            $this->get($url)->assertRedirect(route('verification.notice'));
        }
    }

    public function test_a_verified_account_reaches_the_app(): void
    {
        $this->actingAs(User::factory()->create())->get(route('freezer.closed'))->assertOk();
    }

    public function test_the_reminder_renders_for_an_unverified_account(): void
    {
        $user = $this->unverified();

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertSee($user->email);
    }

    public function test_the_reminder_is_not_shown_to_a_verified_account(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(VerifyEmail::class)
            ->assertRedirect(route('freezer.closed'));
    }

    public function test_the_signed_link_confirms_the_address(): void
    {
        $user = $this->unverified();

        $this->actingAs($user)
            ->get($this->signedLink($user))
            ->assertRedirect(route('freezer.closed'))
            ->assertSessionHas('status');

        $this->assertNotNull($user->refresh()->email_verified_at);

        // From now on the app is open.
        $this->get(route('freezer.closed'))->assertOk();
    }

    public function test_an_unsigned_link_does_not_confirm_anything(): void
    {
        $user = $this->unverified();

        $this->actingAs($user)
            ->get(route('verification.verify', ['id' => $user->getKey(), 'hash' => sha1($user->email)]))
            ->assertForbidden();

        $this->assertNull($user->refresh()->email_verified_at);
    }

    public function test_a_link_of_another_account_does_not_confirm_anything(): void
    {
        $user = $this->unverified();
        $other = $this->unverified();

        // Signed for the other account, but opened while the first one is here.
        $this->actingAs($user)
            ->get($this->signedLink($other))
            ->assertForbidden();

        $this->assertNull($user->refresh()->email_verified_at);
        $this->assertNull($other->refresh()->email_verified_at);
    }

    public function test_a_new_link_can_be_requested_from_the_reminder(): void
    {
        Notification::fake();

        $user = $this->unverified();

        Livewire::actingAs($user)
            ->test(VerifyEmail::class)
            ->call('send')
            ->assertSet('feedback', 'Wir haben dir einen neuen Link an '.$user->email.' geschickt.');

        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_the_verification_mail_is_written_in_german(): void
    {
        Notification::fake();

        $user = $this->unverified();

        Livewire::actingAs($user)->test(VerifyEmail::class)->call('send');

        Notification::assertSentTo(
            $user,
            VerifyEmailNotification::class,
            function (VerifyEmailNotification $notification) use ($user): bool {
                $mail = $notification->toMail($user);

                return $mail->subject === 'Bitte bestätige deine E-Mail-Adresse'
                    && $mail->greeting === 'Hallo '.$user->displayName().'!'
                    && $mail->actionText === 'E-Mail-Adresse bestätigen';
            },
        );
    }

    public function test_the_new_link_is_not_sent_endlessly(): void
    {
        Notification::fake();

        $user = $this->unverified();
        $page = Livewire::actingAs($user)->test(VerifyEmail::class);

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $page->call('send');
        }

        // Five mails leave the house, the sixth click is turned down.
        Notification::assertSentTimes(VerifyEmailNotification::class, 5);
        $page->assertSee('Bitte warte eine Minute');
    }
}
