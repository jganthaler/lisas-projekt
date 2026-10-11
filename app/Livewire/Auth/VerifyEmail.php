<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Reminder that the e-mail address still has to be confirmed.
 *
 * The page is reachable before the account is verified ("verification.notice")
 * and offers a new link, because mails get lost. Everything else stays locked
 * until then - see the "verified" middleware on the app routes.
 */
#[Layout('layouts.auth')]
#[Title('E-Mail bestätigen')]
class VerifyEmail extends Component
{
    public ?string $feedback = null;

    public function mount(): void
    {
        // Nothing to remind of once the address is confirmed.
        if (auth()->user()->hasVerifiedEmail()) {
            $this->redirectRoute('freezer.closed');
        }
    }

    /**
     * Send the verification mail again - but not more often than five times a
     * minute, so the button cannot be used to flood a mailbox.
     */
    public function send(): void
    {
        $user = auth()->user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectRoute('freezer.closed');

            return;
        }

        $key = 'verify-email|'.$user->getKey();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->feedback = 'Bitte warte eine Minute, bevor du einen neuen Link anforderst.';

            return;
        }

        RateLimiter::hit($key, 60);

        $user->sendEmailVerificationNotification();

        $this->feedback = 'Wir haben dir einen neuen Link an '.$user->email.' geschickt.';
    }

    public function render()
    {
        return view('livewire.auth.verify-email', [
            'user' => auth()->user(),
        ]);
    }
}
