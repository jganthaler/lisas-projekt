<div class="space-y-4">
    <div>
        <h2 class="text-base font-bold text-frost-900">Profil</h2>
        <p class="text-xs text-frost-500">Deine Angaben und dein Profilbild</p>
    </div>

    <x-notice :message="$feedback" />

    {{-- Avatar --}}
    <form wire:submit="saveAvatar" class="card space-y-3 p-4">
        <h3 class="text-sm font-semibold text-frost-900">Profilbild</h3>

        <div class="flex items-center gap-4">
            {{-- Only previewable uploads may call temporaryUrl(), a rejected file
                 (for example a PDF) would break the whole page otherwise. --}}
            @if ($avatar && $avatar->isPreviewable())
                <img src="{{ $avatar->temporaryUrl() }}" alt="Vorschau"
                     class="h-16 w-16 shrink-0 rounded-full object-cover ring-2 ring-brand-200">
            @else
                <x-avatar :user="$user" size="h-16 w-16" />
            @endif

            <div class="min-w-0 flex-1">
                <label class="btn btn-secondary w-full cursor-pointer" for="avatar">
                    <x-icon name="camera" class="h-4 w-4" />
                    Bild auswählen
                </label>
                <input id="avatar" type="file" wire:model="avatar" accept="image/*" class="sr-only">

                @error('avatar')
                    <p class="field-error">{{ $message }}</p>
                @enderror

                <p class="mt-1.5 text-xs text-frost-500">JPG, PNG oder WebP · maximal 2 MB</p>
            </div>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary flex-1"
                    wire:loading.attr="disabled" wire:target="avatar,saveAvatar">
                <span wire:loading.remove wire:target="avatar,saveAvatar">Bild speichern</span>
                <span wire:loading wire:target="avatar,saveAvatar">Wird hochgeladen …</span>
            </button>

            @if ($user->hasAvatar())
                <button type="button"
                        wire:click="$dispatch('confirm-dialog', { name: 'avatar-remove' })"
                        class="btn btn-ghost">
                    <x-icon name="trash" class="h-5 w-5" />
                    Entfernen
                </button>

                <x-confirm-dialog
                    name="avatar-remove"
                    title="Profilbild entfernen?"
                    message="Dein Bild wird gelöscht – danach sind wieder deine Initialen zu sehen."
                    action="removeAvatar"
                    confirm-label="Entfernen" />
            @endif
        </div>
    </form>

    {{-- Name and e-mail --}}
    <form wire:submit="updateProfile" class="card space-y-3 p-4">
        <h3 class="text-sm font-semibold text-frost-900">Angaben</h3>

        <div>
            <label class="label" for="name">Name</label>
            <input id="name" type="text" wire:model="name" autocomplete="name"
                   class="input @error('name') input-error @enderror">
            @error('name')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="label" for="email">E-Mail</label>
            <input id="email" type="email" wire:model="email" autocomplete="email" inputmode="email"
                   class="input @error('email') input-error @enderror">
            @error('email')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn btn-secondary w-full" wire:loading.attr="disabled" wire:target="updateProfile">
            <x-icon name="check" class="h-5 w-5" />
            Speichern
        </button>
    </form>

    {{-- Password --}}
    <form wire:submit="updatePassword" class="card space-y-3 p-4">
        <h3 class="text-sm font-semibold text-frost-900">Passwort ändern</h3>

        <div>
            <label class="label" for="currentPassword">Aktuelles Passwort</label>
            <input id="currentPassword" type="password" wire:model="currentPassword" autocomplete="current-password"
                   class="input @error('currentPassword') input-error @enderror">
            @error('currentPassword')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="label" for="newPassword">Neues Passwort</label>
            <input id="newPassword" type="password" wire:model="newPassword" autocomplete="new-password"
                   class="input @error('newPassword') input-error @enderror">
            @error('newPassword')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="label" for="newPassword_confirmation">Neues Passwort wiederholen</label>
            <input id="newPassword_confirmation" type="password" wire:model="newPassword_confirmation"
                   autocomplete="new-password" class="input">
        </div>

        <button type="submit" class="btn btn-secondary w-full" wire:loading.attr="disabled" wire:target="updatePassword">
            <x-icon name="check" class="h-5 w-5" />
            Passwort ändern
        </button>
    </form>

    {{-- Sign out --}}
    <form method="POST" action="{{ route('logout') }}">
        @csrf

        <button type="submit" class="btn btn-ghost w-full">
            <x-icon name="logout" class="h-5 w-5" />
            Abmelden
        </button>
    </form>
</div>
