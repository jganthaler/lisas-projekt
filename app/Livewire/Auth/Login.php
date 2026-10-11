<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.auth')]
#[Title('Anmelden')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = true;

    public function login(): void
    {
        $credentials = $this->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            $this->addError('email', 'Zu viele Anmeldeversuche. Bitte versuche es in einer Minute erneut.');

            return;
        }

        if (! Auth::attempt($credentials, $this->remember)) {
            RateLimiter::hit($this->throttleKey(), 60);

            $this->addError('email', 'E-Mail oder Passwort stimmen nicht.');

            return;
        }

        RateLimiter::clear($this->throttleKey());

        // The guard rotated the session id already. "intended" brings the user
        // back to the page that sent them to the login - e.g. an invitation.
        $this->redirectIntended(route('freezer.closed'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email)).'|'.request()->ip();
    }
}
