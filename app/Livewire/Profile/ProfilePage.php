<?php

namespace App\Livewire\Profile;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Profile of the signed in user: display name, e-mail address, password and
 * the profile picture that is shown in the header.
 */
#[Layout('layouts.app')]
#[Title('Profil')]
class ProfilePage extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $email = '';

    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPassword_confirmation = '';

    /** @var TemporaryUploadedFile|null */
    public $avatar = null;

    public ?string $feedback = null;

    public function mount(): void
    {
        $user = Auth::user();

        $this->name = (string) $user->name;
        $this->email = (string) $user->email;
    }

    public function updateProfile(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->getKey())],
        ]);

        $user->update($validated);

        $this->feedback = 'Profil gespeichert.';
    }

    public function updatePassword(): void
    {
        $validated = $this->validate([
            'currentPassword' => ['required', 'string', 'current_password'],
            'newPassword' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        Auth::user()->update(['password' => $validated['newPassword']]);

        $this->reset('currentPassword', 'newPassword', 'newPassword_confirmation');
        $this->feedback = 'Passwort geändert.';
    }

    public function saveAvatar(): void
    {
        $this->validate([
            'avatar' => ['required', 'image', 'max:2048'],
        ]);

        $user = Auth::user();
        $filename = 'avatar-'.$user->getKey().'-'.Str::lower(Str::random(12)).'.'.($this->avatar->extension() ?: 'jpg');

        $path = Storage::disk('local')->putFileAs('avatars', $this->avatar, $filename);

        if ($path === false) {
            $this->addError('avatar', 'Das Bild konnte nicht gespeichert werden.');

            return;
        }

        $previous = $user->avatar_path;

        $user->forceFill(['avatar_path' => $path])->save();

        if ($previous !== null && $previous !== $path) {
            Storage::disk('local')->delete($previous);
        }

        $this->reset('avatar');
        $this->feedback = 'Profilbild gespeichert.';
    }

    public function removeAvatar(): void
    {
        $user = Auth::user();

        if (! $user->hasAvatar()) {
            return;
        }

        Storage::disk('local')->delete((string) $user->avatar_path);

        $user->forceFill(['avatar_path' => null])->save();

        $this->feedback = 'Profilbild entfernt.';
    }

    public function render()
    {
        return view('livewire.profile.profile-page', [
            'user' => Auth::user(),
        ]);
    }
}
