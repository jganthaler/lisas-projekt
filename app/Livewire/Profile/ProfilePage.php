<?php

namespace App\Livewire\Profile;

use App\Support\Locales;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Profile of the signed in user: display name, e-mail address, password, the
 * profile picture that is shown in the header and the language of the app.
 */
#[Layout('layouts.app')]
#[Title('profile.title')]
class ProfilePage extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $email = '';

    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPassword_confirmation = '';

    /** Passwort, mit dem die Löschung des Kontos bestätigt wird. */
    public string $deletePassword = '';

    /** Code of the language the app is shown in (see App\Support\Locales). */
    public string $locale = '';

    /** @var TemporaryUploadedFile|null */
    public $avatar = null;

    public ?string $feedback = null;

    public function mount(): void
    {
        $user = Auth::user();

        $this->name = (string) $user->name;
        $this->email = (string) $user->email;
        $this->locale = $user->localeOrDefault();
    }

    public function updateProfile(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->getKey())],
        ]);

        $user->update($validated);

        $this->feedback = __('profile.saved');
    }

    /**
     * Switch the app to another language: the choice is stored on the account
     * and the locale of this request is set right away, so the answer already
     * arrives in the new language. Every following request picks the choice up
     * from the account (see App\Http\Middleware\SetLocale).
     */
    public function setLocale(string $locale): void
    {
        // An unknown code cannot be chosen in the profile - it only shows up
        // when somebody builds the request by hand.
        if (! Locales::supports($locale)) {
            return;
        }

        Auth::user()->update(['locale' => $locale]);

        App::setLocale($locale);

        $this->locale = $locale;
        $this->feedback = __('profile.language_saved');
    }

    public function updatePassword(): void
    {
        $validated = $this->validate([
            'currentPassword' => ['required', 'string', 'current_password'],
            'newPassword' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        Auth::user()->update(['password' => $validated['newPassword']]);

        $this->reset('currentPassword', 'newPassword', 'newPassword_confirmation');
        $this->feedback = __('profile.password_changed');
    }

    /**
     * The profile picture is stored as soon as it has been chosen: Livewire calls
     * this hook right after the upload of the field, so there is no save button
     * and picking another image simply replaces the current one.
     */
    public function updatedAvatar(): void
    {
        // The field is emptied after saving (and cleared when an upload is taken
        // back) - then there is nothing to store.
        if (! $this->avatar instanceof UploadedFile) {
            return;
        }

        $this->validate([
            'avatar' => ['image', 'max:2048'],
        ]);

        $user = Auth::user();
        $filename = 'avatar-'.$user->getKey().'-'.Str::lower(Str::random(12)).'.'.($this->avatar->extension() ?: 'jpg');

        $path = Storage::disk('local')->putFileAs('avatars', $this->avatar, $filename);

        if ($path === false) {
            $this->addError('avatar', __('profile.image_failed'));

            return;
        }

        $previous = $user->avatar_path;

        $user->forceFill(['avatar_path' => $path])->save();

        if ($previous !== null && $previous !== $path) {
            Storage::disk('local')->delete($previous);
        }

        $this->reset('avatar');
        $this->feedback = __('profile.image_saved');
    }

    public function removeAvatar(): void
    {
        $user = Auth::user();

        if (! $user->hasAvatar()) {
            return;
        }

        Storage::disk('local')->delete((string) $user->avatar_path);

        $user->forceFill(['avatar_path' => null])->save();

        $this->feedback = __('profile.image_removed');
    }

    /**
     * Das Konto löschen - das Recht auf Löschung (Art. 17 DSGVO) muss ohne
     * Umweg über den Anbieter zu erfüllen sein.
     *
     * Das Passwort wird erneut verlangt, damit eine übernommene Sitzung nicht
     * ausreicht. Mit dem Konto gehen die eigenen Gefrierschränke samt
     * Schubladen und Artikeln; das räumen die Fremdschlüssel auf. Geteilte
     * Gefrierschränke anderer bleiben bestehen - dort verschwindet nur die
     * Mitgliedschaft.
     *
     * Neben der Zeile in der Datenbank verschwinden auch das Profilbild, die
     * Sitzungen und ein offener Link zum Zurücksetzen des Passworts: Sonst
     * blieben personenbezogene Daten zurück.
     */
    public function deleteAccount(): void
    {
        $user = Auth::user();

        $this->validate([
            'deletePassword' => ['required', 'string', 'current_password'],
        ]);

        // Das Bild liegt außerhalb der Datenbank und würde die Zeile überleben.
        if ($user->hasAvatar()) {
            Storage::disk('local')->delete((string) $user->avatar_path);
        }

        DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        DB::table('sessions')->where('user_id', $user->getKey())->delete();

        Auth::logout();

        $user->delete();

        // Eine frische Sitzung: nichts des gelöschten Kontos bleibt darin.
        session()->invalidate();
        session()->regenerateToken();
        session()->flash('status', __('profile.account_deleted'));

        $this->redirectRoute('login', navigate: true);
    }

    public function render()
    {
        return view('livewire.profile.profile-page', [
            'user' => Auth::user(),
            'languages' => Locales::ALL,
        ]);
    }
}
