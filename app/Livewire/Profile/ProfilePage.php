<?php

namespace App\Livewire\Profile;

use App\Support\AvatarImage;
use App\Support\Locales;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Profile of the signed in user: display name, password, the profile picture
 * that is shown in the header and the language of the app.
 *
 * The e-mail address is only shown here: it is confirmed and it is the name of
 * the account (see updateProfile()).
 */
#[Layout('layouts.app')]
#[Title('profile.title')]
class ProfilePage extends Component
{
    use WithFileUploads;

    /**
     * Largest picture we take from an upload, in kilobytes - 10 MB. A photo
     * from a phone is bigger than that only in rare cases, and what is stored
     * is smaller than the upload by orders of magnitude (see the hint texts in
     * lang/<sprache>/profile.php).
     */
    private const MAX_AVATAR_KB = 10240;

    /**
     * The picture types of a profile: everything else could not be shrunk (a
     * HEIC from a phone, an animated GIF) or not be shown in the browser at
     * all. See App\Support\AvatarImage.
     */
    private const AVATAR_TYPES = 'jpg,jpeg,png,webp';

    public string $name = '';

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
        $this->locale = $user->localeOrDefault();
    }

    /**
     * Der Name lässt sich ändern, die E-Mail-Adresse nicht: Sie ist bestätigt,
     * sie ist der Name des Kontos und der Empfänger der Links zum Zurücksetzen
     * des Passworts. Wer sie ändern könnte, würde die Bestätigung stillschweigend
     * auf eine unbestätigte Adresse übertragen. So bleibt jede Person bei der
     * Adresse, mit der sie sich angemeldet und die sie bestätigt hat; das Profil
     * liegt ohnehin hinter "verified", es erreicht also nur bestätigte Konten.
     */
    public function updateProfile(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:60'],
        ]);

        Auth::user()->update($validated);

        $this->feedback = __('profile.saved');
    }

    /**
     * Wechselt die Sprache der App: die Wahl wird am Konto gespeichert und die
     * Sprache dieser Anfrage sofort umgestellt, damit die Rückmeldung schon in
     * der neuen Sprache formuliert ist.
     *
     * Danach wird die Seite geladen. Kopfzeile und untere Leiste liegen im
     * Layout außerhalb dieser Komponente und werden von einem Livewire-Update
     * nicht neu geschrieben - ohne das Neuladen blieben "Gefrierschrank",
     * "Inventar" und "MHD" in der alten Sprache stehen. Jede weitere Anfrage
     * übernimmt die Wahl ohnehin vom Konto (siehe
     * App\Http\Middleware\SetLocale).
     */
    public function setLocale(string $locale): void
    {
        // An unknown code cannot be chosen in the profile - it only shows up
        // when somebody builds the request by hand.
        if (! Locales::supports($locale)) {
            return;
        }

        Auth::user()->update(['locale' => $locale]);

        // Notwendig, bevor die Rückmeldung formuliert wird: sie soll schon in
        // der neuen Sprache in der Sitzung liegen.
        App::setLocale($locale);

        session()->flash('status', __('profile.language_saved'));

        $this->redirectRoute('profile.edit', navigate: true);
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
     *
     * Ein Foto vom Handy ist ein paar tausend Pixel breit und mehrere Megabyte
     * groß - für einen runden Avatar von 36 Pixeln viel zu viel und für den
     * Upload im Mobilfunknetz zu schwer. Was zu groß ist, wird deshalb vor dem
     * Speichern auf eine passende Größe gebracht (App\Support\AvatarImage).
     *
     * Danach wird die Seite geladen - wie beim Wechsel der Sprache: Der Avatar
     * der Kopfzeile liegt im Layout außerhalb dieser Komponente und bliebe sonst
     * beim alten Bild stehen (oder bei den Initialen, obwohl jetzt ein Bild da
     * ist). Jede weitere Anfrage liest das Bild ohnehin vom Konto
     * (User::avatarUrl()).
     */
    public function updatedAvatar(): void
    {
        // The field is emptied after saving (and cleared when an upload is taken
        // back) - then there is nothing to store.
        if (! $this->avatar instanceof UploadedFile) {
            return;
        }

        $this->validate([
            'avatar' => ['image', 'mimes:'.self::AVATAR_TYPES, 'max:'.self::MAX_AVATAR_KB],
        ], [
            'avatar.image' => __('profile.image_invalid'),
            'avatar.mimes' => __('profile.image_invalid'),
            'avatar.max' => __('profile.image_too_large'),
        ]);

        $user = Auth::user();
        $image = AvatarImage::fromUpload($this->avatar);
        $filename = 'avatar-'.$user->getKey().'-'.Str::lower(Str::random(12)).'.'.$image->extension();
        $path = 'avatars/'.$filename;

        // Gespeichert werden die verkleinerten Bytes, nicht der Upload selbst:
        // Aus mehreren Megabyte werden so wenige Kilobyte.
        if (Storage::disk('local')->put($path, $image->contents()) === false) {
            $this->addError('avatar', __('profile.image_failed'));

            return;
        }

        $previous = $user->avatar_path;

        $user->forceFill(['avatar_path' => $path])->save();

        if ($previous !== null && $previous !== $path) {
            Storage::disk('local')->delete($previous);
        }

        $this->reset('avatar');

        session()->flash('status', __('profile.image_saved'));

        $this->redirectRoute('profile.edit', navigate: true);
    }

    /**
     * Das Profilbild entfernen: Die Datei verschwindet vom Speicher, der Pfad aus
     * dem Konto, und die Kopfzeile zeigt danach wieder die Initialen.
     *
     * Die Seite wird auch hier geladen, aus demselben Grund wie beim Speichern:
     * Der Avatar der Kopfzeile liegt außerhalb dieser Komponente und bliebe sonst
     * beim gelöschten Bild stehen.
     */
    public function removeAvatar(): void
    {
        $user = Auth::user();

        if (! $user->hasAvatar()) {
            return;
        }

        Storage::disk('local')->delete((string) $user->avatar_path);

        $user->forceFill(['avatar_path' => null])->save();

        session()->flash('status', __('profile.image_removed'));

        $this->redirectRoute('profile.edit', navigate: true);
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
