<div class="space-y-4">
    @if ($invitation === null || $invitation->freezer === null)
        <div class="card px-5 py-8 text-center">
            <span class="mx-auto grid h-14 w-14 place-items-center rounded-control bg-red-50 text-red-600 dark:bg-red-950 dark:text-red-300">
                <x-icon name="alert" class="h-7 w-7" />
            </span>

            <h2 class="mt-3 text-base font-bold text-frost-900">Einladung nicht gefunden</h2>
            <p class="mt-1 text-sm text-frost-500">
                Dieser Link gehört zu keinem Gefrierschrank mehr. Bitte lasse dir einen neuen schicken.
            </p>

            <a href="{{ route('freezer.closed') }}" wire:navigate class="btn btn-primary mt-5 w-full">
                Zu meinem Gefrierschrank
            </a>
        </div>
    @else
        <div class="card px-5 py-8 text-center">
            <span class="mx-auto grid h-14 w-14 place-items-center rounded-control bg-brand-600 text-white">
                <x-icon name="share" class="h-7 w-7" />
            </span>

            <h2 class="mt-3 text-base font-bold text-frost-900">
                Einladung zu „{{ $invitation->freezer->name }}“
            </h2>
            <p class="mt-1 text-sm text-frost-500">
                {{ $invitation->inviter?->displayName() ?? 'Jemand' }} möchte den Gefrierschrank mit dir teilen.
            </p>

            @if ($alreadyMember)
                <p class="badge badge-ok mt-3">Du bist hier schon Mitglied</p>
            @endif

            @if ($error)
                <x-notice :message="$error" variant="error" class="mt-4" />
            @endif

            @if ($alreadyMember || $invitation->isUsable())
                <div class="mt-6 space-y-2">
                    <button type="button" wire:click="accept" class="btn btn-primary w-full">
                        <x-icon name="check" class="h-5 w-5" />
                        Einladung annehmen
                    </button>

                    <button type="button" wire:click="decline" class="btn btn-ghost w-full">
                        Später
                    </button>
                </div>
            @else
                <p class="mt-5 rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-200">
                    Diese Einladung ist
                    {{ $invitation->isExpired() ? 'abgelaufen' : 'bereits verwendet worden' }}.
                    Bitte lasse dir einen neuen Link schicken.
                </p>

                <a href="{{ route('freezer.closed') }}" wire:navigate class="btn btn-secondary mt-4 w-full">
                    Zu meinem Gefrierschrank
                </a>
            @endif
        </div>
    @endif
</div>
