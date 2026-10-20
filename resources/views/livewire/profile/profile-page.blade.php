<div class="space-y-4">
    <div>
        <h2 class="text-base font-bold text-frost-900">{{ __('profile.title') }}</h2>
        <p class="text-xs text-frost-500">{{ __('profile.subtitle') }}</p>
    </div>

    <x-notice :message="$feedback" />

    {{-- Avatar: the chosen image is uploaded and stored right away, so there is
         no save button - picking another image simply replaces the current one. --}}
    <section class="card space-y-3 p-4">
        <h3 class="text-sm font-semibold text-frost-900">{{ __('profile.avatar') }}</h3>

        <div class="flex items-center gap-4">
            {{-- Only previewable uploads may call temporaryUrl(), a rejected file
                 (for example a PDF) would break the whole page otherwise. --}}
            @if ($avatar && $avatar->isPreviewable())
                <img src="{{ $avatar->temporaryUrl() }}" alt="{{ __('profile.avatar_preview') }}"
                     class="h-16 w-16 shrink-0 rounded-full object-cover ring-2 ring-brand-200">
            @else
                <x-avatar :user="$user" size="h-16 w-16" />
            @endif

            <div class="min-w-0 flex-1">
                <label class="btn btn-secondary w-full cursor-pointer" for="avatar">
                    <x-icon name="camera" class="h-4 w-4" />
                    {{ __('profile.choose_image') }}
                </label>
                {{-- accept: nur die Formate, die die App verkleinern und im
                     Browser anzeigen kann - ein iPhone wandelt HEIC dabei
                     selbst in JPEG um. --}}
                <input id="avatar" type="file" wire:model="avatar"
                       accept="image/jpeg,image/png,image/webp" class="sr-only">

                @error('avatar')
                    <p class="field-error">{{ $message }}</p>
                @enderror

                {{-- Nur die Rückmeldung während des Hochladens; der Absatz ist
                     sonst ausgeblendet (wire:loading). Was angenommen wird, sagt
                     schon das Fenster der Dateiauswahl (accept), und ein zu
                     großes Bild meldet sich selbst. --}}
                <p wire:loading wire:target="avatar" class="mt-1.5 text-xs text-frost-500">
                    {{ __('profile.uploading') }}
                </p>
            </div>
        </div>

        @if ($user?->hasAvatar())
            <button type="button"
                    wire:click="$dispatch('confirm-dialog', { name: 'avatar-remove' })"
                    class="btn btn-ghost w-full">
                <x-icon name="trash" class="h-5 w-5" />
                {{ __('common.remove') }}
            </button>

            <x-confirm-dialog
                name="avatar-remove"
                :title="__('profile.remove_avatar_title')"
                :message="__('profile.remove_avatar_message')"
                action="removeAvatar"
                :confirm-label="__('common.remove')" />
        @endif
    </section>

    {{-- Sprache der App: jede Wahl wird sofort gespeichert und lädt die Seite
         neu - Kopfzeile und untere Leiste liegen außerhalb dieser Komponente
         und bleiben sonst in der alten Sprache stehen (siehe
         ProfilePage::setLocale()). --}}
    <section class="card space-y-3 p-4">
        <h3 class="text-sm font-semibold text-frost-900">{{ __('profile.language_heading') }}</h3>

        <div class="flex flex-wrap gap-1.5" role="radiogroup" aria-label="{{ __('profile.language_heading') }}">
            @foreach ($languages as $code => $label)
                <button type="button" wire:click="setLocale('{{ $code }}')" role="radio"
                        wire:key="locale-{{ $code }}"
                        aria-checked="{{ $locale === $code ? 'true' : 'false' }}"
                        class="badge rounded-control px-3 py-1.5 {{ $locale === $code ? 'badge-ok' : 'badge-neutral' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </section>

    {{-- Die installierte App: Der Abschnitt bleibt verborgen, bis der Browser
         die Installation anbietet (Chrome, Edge, Android) oder ein iPhone den
         Umweg über das Teilen-Menü braucht - auf einem Rechner und in einer
         schon installierten App erscheint er nie. wire:ignore, weil das
         Ein- und Ausblenden Sache des Skripts ist (resources/js/app.js); die
         Texte liegen in der Sprache, die die Seite geladen hat. --}}
    <section class="card space-y-3 p-4" data-install-section hidden wire:ignore>
        <h3 class="text-sm font-semibold text-frost-900">{{ __('profile.install_heading') }}</h3>

        <p class="text-xs text-frost-500" data-install-ios hidden>{{ __('profile.install_ios') }}</p>

        <button type="button" class="btn btn-secondary w-full" data-install-button hidden>
            <x-icon name="install" class="h-5 w-5" />
            {{ __('profile.install_button') }}
        </button>
    </section>

    {{-- Name and e-mail --}}
    <form wire:submit="updateProfile" class="card space-y-3 p-4">
        <h3 class="text-sm font-semibold text-frost-900">{{ __('profile.details') }}</h3>

        <div>
            <label class="label" for="name">{{ __('common.name') }}</label>
            <input id="name" type="text" wire:model="name" autocomplete="name"
                   class="input @error('name') input-error @enderror">
            @error('name')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        {{-- Die bestätigte E-Mail-Adresse steht nur noch hier: Sie ist der Name
             des Kontos und der Empfänger der Links zum Zurücksetzen, ein Wechsel
             würde die Bestätigung entwerten (siehe ProfilePage::updateProfile()).
             Ein Feld dafür gibt es deshalb nicht mehr. Das Häkchen sitzt rechts
             der Adresse, damit die Adresse selbst links beginnt wie in einem
             echten Feld. --}}
        <div>
            <span class="label">{{ __('common.email') }}</span>
            <p class="input flex items-center justify-between gap-2">
                <span class="min-w-0 break-all">{{ $user->email }}</span>
                <x-icon name="check" class="h-4 w-4 shrink-0 text-brand-600 dark:text-brand-400" />
            </p>
            <p class="mt-1.5 text-xs text-frost-500">{{ __('profile.email_locked') }}</p>
        </div>

        <button type="submit" class="btn btn-secondary w-full" wire:loading.attr="disabled" wire:target="updateProfile">
            <x-icon name="check" class="h-5 w-5" />
            {{ __('common.save') }}
        </button>
    </form>

    {{-- Password --}}
    <form wire:submit="updatePassword" class="card space-y-3 p-4">
        <h3 class="text-sm font-semibold text-frost-900">{{ __('profile.password_heading') }}</h3>

        <div>
            <label class="label" for="currentPassword">{{ __('profile.current_password') }}</label>
            <input id="currentPassword" type="password" wire:model="currentPassword" autocomplete="current-password"
                   class="input @error('currentPassword') input-error @enderror">
            @error('currentPassword')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="label" for="newPassword">{{ __('profile.new_password') }}</label>
            <input id="newPassword" type="password" wire:model="newPassword" autocomplete="new-password"
                   class="input @error('newPassword') input-error @enderror">
            @error('newPassword')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="label" for="newPassword_confirmation">{{ __('profile.password_confirmation') }}</label>
            <input id="newPassword_confirmation" type="password" wire:model="newPassword_confirmation"
                   autocomplete="new-password" class="input">
        </div>

        <button type="submit" class="btn btn-secondary w-full" wire:loading.attr="disabled" wire:target="updatePassword">
            <x-icon name="check" class="h-5 w-5" />
            {{ __('profile.password_heading') }}
        </button>
    </form>

    {{-- Rechtliches: Impressum, Datenschutzerklärung und Nutzungsbedingungen.
         Sie liegen außerhalb der App, weil sie auch ohne Konto lesbar sein
         müssen (siehe App\Http\Controllers\LegalController). --}}
    <section class="card space-y-2 p-4">
        <h3 class="text-sm font-semibold text-frost-900">{{ __('profile.legal_heading') }}</h3>

        <div class="flex flex-col">
            @foreach (['legal.imprint' => 'imprint', 'legal.privacy' => 'privacy', 'legal.terms' => 'terms'] as $route => $document)
                <a href="{{ route($route) }}"
                   class="flex items-center gap-2 rounded-control px-1 py-2 text-sm font-medium text-frost-700 hover:text-brand-700 dark:hover:text-brand-400">
                    <x-icon name="chevron-right" class="h-4 w-4 text-frost-400" />
                    {{ __('legal.'.$document.'.title') }}
                </a>
            @endforeach
        </div>
    </section>

    {{-- Das Konto löschen: Art. 17 DSGVO verlangt, dass das ohne Umweg über den
         Anbieter möglich ist. Das Passwort wird erneut verlangt, weil eine
         übernommene Sitzung sonst ausreichen würde. --}}
    <section class="card space-y-3 p-4">
        <div>
            <h3 class="text-sm font-semibold text-danger-700 dark:text-danger-300">{{ __('profile.delete_heading') }}</h3>
            <p class="mt-1 text-xs text-frost-500">{{ __('profile.delete_hint') }}</p>
        </div>

        <div>
            <label class="label" for="deletePassword">{{ __('profile.delete_password') }}</label>
            <input id="deletePassword" type="password" wire:model="deletePassword" autocomplete="current-password"
                   class="input @error('deletePassword') input-error @enderror">
            @error('deletePassword')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <button type="button"
                wire:click="$dispatch('confirm-dialog', { name: 'account-delete' })"
                class="btn btn-danger w-full">
            <x-icon name="trash" class="h-5 w-5" />
            {{ __('profile.delete_button') }}
        </button>

        <x-confirm-dialog
            name="account-delete"
            :title="__('profile.delete_confirm_title')"
            :message="__('profile.delete_confirm_message')"
            action="deleteAccount"
            :confirm-label="__('profile.delete_button')" />
    </section>

    {{-- Sign out --}}
    <form method="POST" action="{{ route('logout') }}">
        @csrf

        <button type="submit" class="btn btn-ghost w-full">
            <x-icon name="logout" class="h-5 w-5" />
            {{ __('common.logout') }}
        </button>
    </form>
</div>
