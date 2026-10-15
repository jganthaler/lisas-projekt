<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The page behind the link of the reset mail.
 *
 * The token comes from the route, the address from the query string of the
 * link; both are handed to the password broker, which checks that the token
 * belongs to this account and has not expired yet. Only then the new password
 * is stored - hashed by the "password" cast of the user model.
 */
#[Layout('layouts.auth')]
#[Title('auth.reset.title')]
class ResetPassword extends Component
{
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;

        // The address travels with the link, so the form is filled in already
        // and the user only has to choose a new password.
        $this->email = (string) request()->query('email', '');
    }

    public function resetPassword(): void
    {
        $validated = $this->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            [
                'email' => $validated['email'],
                'password' => $validated['password'],
                'password_confirmation' => $this->password_confirmation,
                'token' => $this->token,
            ],
            function (User $user, string $password): void {
                $user->forceFill(['password' => $password])
                    ->setRememberToken(Str::random(60));

                $user->save();

                // The event is the hook the app can react to. The fresh
                // remember token above already logs other devices out.
                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            $this->addError('email', __('auth.reset.invalid_link'));

            return;
        }

        session()->flash('status', __('auth.reset.status'));

        $this->redirectRoute('login', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.reset-password');
    }
}
