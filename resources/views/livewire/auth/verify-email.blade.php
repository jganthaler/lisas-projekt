<div>
    <h2 class="text-lg font-bold text-frost-900">E-Mail-Adresse bestätigen</h2>
    <p class="mt-1 text-sm text-frost-500">
        Wir haben dir einen Link an
        <span class="font-medium text-frost-700">{{ $user->email }}</span>
        geschickt. Bitte klicke darauf, um deinen Gefrierschrank freizuschalten.
    </p>

    <x-notice :message="$feedback" class="mt-4" />

    <button type="button" wire:click="send" class="btn btn-primary mt-4 w-full"
            wire:loading.attr="disabled" wire:target="send">
        <span wire:loading.remove wire:target="send">Link erneut senden</span>
        <span wire:loading wire:target="send">Wird gesendet …</span>
    </button>

    <form method="POST" action="{{ route('logout') }}" class="mt-3">
        @csrf

        <button type="submit" class="btn btn-ghost w-full">
            <x-icon name="logout" class="h-5 w-5" />
            Abmelden
        </button>
    </form>
</div>
