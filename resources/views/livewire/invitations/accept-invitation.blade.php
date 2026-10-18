<div class="space-y-4">
    @if ($invitation === null || $invitation->freezer === null)
        <div class="card px-5 py-8 text-center">
            <span class="mx-auto grid h-14 w-14 place-items-center rounded-control bg-danger-50 text-danger-600 dark:bg-danger-950 dark:text-danger-300">
                <x-icon name="alert" class="h-7 w-7" />
            </span>

            <h2 class="mt-3 text-base font-bold text-frost-900">{{ __('invitations.not_found') }}</h2>
            <p class="mt-1 text-sm text-frost-500">
                {{ __('invitations.not_found_hint') }}
            </p>

            <a href="{{ route('freezer.closed') }}" wire:navigate class="btn btn-primary mt-5 w-full">
                {{ __('invitations.to_my_freezer') }}
            </a>
        </div>
    @else
        <div class="card px-5 py-8 text-center">
            <span class="mx-auto grid h-14 w-14 place-items-center rounded-control bg-brand-600 text-white">
                <x-icon name="share" class="h-7 w-7" />
            </span>

            <h2 class="mt-3 text-base font-bold text-frost-900">
                {{ __('invitations.heading', ['name' => $invitation->freezer->name]) }}
            </h2>
            <p class="mt-1 text-sm text-frost-500">
                {{ __('invitations.body', ['name' => $invitation->inviter?->displayName() ?? __('common.someone')]) }}
            </p>

            @if ($alreadyMember)
                <p class="badge badge-ok mt-3">{{ __('invitations.already_member') }}</p>
            @endif

            @if ($error)
                <x-notice :message="$error" variant="error" class="mt-4" />
            @endif

            @if ($alreadyMember)
                {{-- Wer schon Mitglied ist, wird nur in den Gefrierschrank
                     gebracht: der Link bleibt dabei offen für die eingeladene
                     Person (siehe AcceptInvitation::accept()). --}}
                <div class="mt-6 space-y-2">
                    <button type="button" wire:click="accept" class="btn btn-primary w-full">
                        <x-icon name="freezer" class="h-5 w-5" />
                        {{ __('invitations.open_freezer') }}
                    </button>

                    <button type="button" wire:click="decline" class="btn btn-ghost w-full">
                        {{ __('invitations.later') }}
                    </button>
                </div>
            @elseif ($invitation->isUsable())
                <div class="mt-6 space-y-2">
                    <button type="button" wire:click="accept" class="btn btn-primary w-full">
                        <x-icon name="check" class="h-5 w-5" />
                        {{ __('invitations.accept') }}
                    </button>

                    <button type="button" wire:click="decline" class="btn btn-ghost w-full">
                        {{ __('invitations.later') }}
                    </button>
                </div>
            @else
                <p class="mt-5 rounded-2xl bg-warning-50 px-4 py-3 text-sm text-warning-800 dark:bg-warning-950 dark:text-warning-200">
                    {{ __('invitations.unusable', [
                        'state' => $invitation->isExpired() ? __('invitations.state_expired') : __('invitations.state_used'),
                    ]) }}
                </p>

                <a href="{{ route('freezer.closed') }}" wire:navigate class="btn btn-secondary mt-4 w-full">
                    {{ __('invitations.to_my_freezer') }}
                </a>
            @endif
        </div>
    @endif
</div>
