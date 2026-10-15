<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * "Passwort vergessen": the user types the address of the account and the
 * broker mails a link with a one-time token to it.
 *
 * The answer on screen never tells whether an account exists for the address -
 * otherwise the form could be used to find out who is registered here.
 */
#[Layout('layouts.auth')]
#[Title('auth.forgot.title')]
class ForgotPassword extends Component
{
    public string $email = '';

    /** Whether the neutral answer ("if there is an account, the mail is on its way") is shown. */
    public bool $sent = false;

    public function send(): void
    {
        $validated = $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $key = 'password-link|'.Str::transliterate(Str::lower($validated['email'])).'|'.request()->ip();

        // A turned down request shows the form again - the notice of a former
        // success would only be in the way of the error message.
        $this->sent = false;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('email', __('auth.forgot.throttled'));

            return;
        }

        RateLimiter::hit($key, 60);

        $status = Password::sendResetLink($validated);

        // Only a request that came in too fast is turned down - the broker
        // throttles per account, everything else gets the same answer.
        if ($status === Password::RESET_THROTTLED) {
            $this->addError('email', __('auth.forgot.already_requested'));

            return;
        }

        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.auth.forgot-password');
    }
}
