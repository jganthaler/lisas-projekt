<div>
    <h2 class="text-lg font-bold text-frost-900">Willkommen zurück</h2>
    <p class="mt-1 text-sm text-frost-500">Melde dich an, um deinen Gefrierschrank zu öffnen.</p>

    <form wire:submit="login" class="mt-5 space-y-4">
        <div>
            <label class="label" for="email">E-Mail</label>
            <input id="email" type="email" wire:model="email" autocomplete="email" inputmode="email" required autofocus
                   class="input @error('email') input-error @enderror">
            @error('email')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="label" for="password">Passwort</label>
            <input id="password" type="password" wire:model="password" autocomplete="current-password" required
                   class="input @error('password') input-error @enderror">
            @error('password')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex cursor-pointer items-center gap-2 text-sm text-frost-700">
            <input type="checkbox" wire:model="remember" class="checkbox">
            Angemeldet bleiben
        </label>

        <button type="submit" class="btn btn-primary w-full" wire:loading.attr="disabled" wire:target="login">
            <span wire:loading.remove wire:target="login">Anmelden</span>
            <span wire:loading wire:target="login">Einen Moment …</span>
        </button>
    </form>

    <p class="mt-5 text-center text-sm text-frost-600">
        Noch kein Konto?
        <a href="{{ route('register') }}" wire:navigate class="font-semibold text-brand-700">Jetzt registrieren</a>
    </p>
</div>
