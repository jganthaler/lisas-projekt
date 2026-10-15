<div>
    <h2 class="text-lg font-bold text-frost-900">Konto anlegen</h2>
    <p class="mt-1 text-sm text-frost-500">
        Dein eigener Gefrierschrank mit drei Schubladen wartet schon auf dich.
    </p>

    <form wire:submit="register" class="mt-5 space-y-4">
        <div>
            <label class="label" for="name">Name</label>
            <input id="name" type="text" wire:model="name" autocomplete="name" required autofocus
                   class="input @error('name') input-error @enderror">
            @error('name')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="label" for="email">E-Mail</label>
            <input id="email" type="email" wire:model="email" autocomplete="email" inputmode="email" required
                   class="input @error('email') input-error @enderror">
            @error('email')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="label" for="password">Passwort</label>
            <input id="password" type="password" wire:model="password" autocomplete="new-password" required
                   class="input @error('password') input-error @enderror">
            @error('password')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="label" for="password_confirmation">Passwort wiederholen</label>
            <input id="password_confirmation" type="password" wire:model="password_confirmation"
                   autocomplete="new-password" required class="input">
        </div>

        <button type="submit" class="btn btn-primary w-full" wire:loading.attr="disabled" wire:target="register">
            <span wire:loading.remove wire:target="register">Registrieren</span>
            <span wire:loading wire:target="register">Konto wird angelegt …</span>
        </button>
    </form>

    <p class="mt-5 text-center text-sm text-frost-600">
        Schon registriert?
        <a href="{{ route('login') }}" wire:navigate class="font-semibold text-brand-700 dark:text-brand-400">Zur Anmeldung</a>
    </p>
</div>
