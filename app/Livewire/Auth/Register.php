<?php

namespace App\Livewire\Auth;

use App\Models\Freezer;
use App\Models\User;
use App\Support\FreezerContext;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.auth')]
#[Title('auth.register.title')]
class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        Auth::login($user);

        // A new account starts with its own freezer and three drawers.
        FreezerContext::select(Freezer::provisionFor($user));

        // The freezer stays locked until the address is confirmed: the mail
        // with the signed link is on its way, the reminder page waits.
        $user->sendEmailVerificationNotification();

        session()->flash('status', __('auth.register.status', ['email' => $user->email]));

        // An intended URL (for example an invitation) stays in the session and
        // is picked up again once the address is confirmed.
        $this->redirectRoute('verification.notice', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}
