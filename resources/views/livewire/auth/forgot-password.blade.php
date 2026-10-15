<div>
    <h2 class="text-lg font-bold text-frost-900">Passwort vergessen?</h2>
    <p class="mt-1 text-sm text-frost-500">
        Kein Problem: Gib deine E-Mail-Adresse ein, dann schicken wir dir einen Link zum
        Zurücksetzen. Er ist eine Stunde lang gültig.
    </p>

    @if ($sent)
        <x-notice class="mt-4"
                  :message="'Wenn es ein Konto zu '.$email.' gibt, ist die E-Mail mit dem Link unterwegs. Schau auch im Spam-Ordner nach.'" />

        <p class="mt-3 text-xs text-frost-500">
            Nichts angekommen? Dann warte einen Moment und fordere den Link erneut an.
        </p>
    @endif

    <form wire:submit="send" class="mt-5 space-y-4">
        <div>
            <label class="label" for="email">E-Mail</label>
            <input id="email" type="email" wire:model="email" autocomplete="email" inputmode="email" required autofocus
                   class="input @error('email') input-error @enderror">
            @error('email')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary w-full" wire:loading.attr="disabled" wire:target="send">
            <span wire:loading.remove wire:target="send">Link zusenden</span>
            <span wire:loading wire:target="send">Wird gesendet …</span>
        </button>
    </form>

    <p class="mt-5 text-center text-sm text-frost-600">
        <a href="{{ route('login') }}" wire:navigate class="font-semibold text-brand-700 dark:text-brand-400">
            Zurück zur Anmeldung
        </a>
    </p>
</div>
