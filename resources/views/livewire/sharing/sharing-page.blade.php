<div class="space-y-4">
    <div>
        <h2 class="text-base font-bold text-frost-900">Teilen</h2>
        <p class="text-xs text-frost-500">{{ $freezer->name }}</p>
    </div>

    <x-notice :message="$feedback" />

    @error('members')
        <x-notice :message="$message" variant="error" />
    @enderror

    @error('leave')
        <x-notice :message="$message" variant="error" />
    @enderror

    {{-- Switch between all freezers the user has access to --}}
    @if ($freezers->count() > 1)
        <section class="card p-1">
            <h3 class="px-3 pb-1 pt-3 text-sm font-semibold text-frost-700">Meine Gefrierschränke</h3>

            @foreach ($freezers as $option)
                <button type="button" wire:click="selectFreezer({{ $option->id }})"
                        class="flex w-full items-center gap-3 rounded-control px-3 py-2.5 text-left transition hover:bg-frost-50"
                        wire:key="freezer-{{ $option->id }}">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-control {{ $option->is($freezer) ? 'bg-brand-600 text-white' : 'bg-frost-100 text-frost-500' }}">
                        <x-icon name="freezer" class="h-5 w-5" />
                    </span>

                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-semibold text-frost-900">{{ $option->name }}</span>
                        <span class="block text-xs text-frost-500">
                            {{ $option->isOwnedBy(auth()->user()) ? 'Dein Gefrierschrank' : 'Mit dir geteilt' }}
                        </span>
                    </span>

                    @if ($option->is($freezer))
                        <x-icon name="check" class="h-5 w-5 shrink-0 text-brand-600" />
                    @endif
                </button>
            @endforeach
        </section>
    @endif

    {{-- Name of the freezer --}}
    @if ($isOwner)
        <form wire:submit="renameFreezer" class="card space-y-3 p-4">
            <h3 class="text-sm font-semibold text-frost-900">Name</h3>

            <div>
                <label class="label" for="freezerName">Gefrierschrank heißt</label>
                <input id="freezerName" type="text" wire:model="freezerName"
                       class="input @error('freezerName') input-error @enderror">
                @error('freezerName')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn btn-secondary w-full">
                <x-icon name="check" class="h-5 w-5" />
                Namen speichern
            </button>
        </form>
    @endif

    {{-- Invite somebody new --}}
    @if ($isOwner)
        <section class="card space-y-3 p-4">
            <div>
                <h3 class="text-sm font-semibold text-frost-900">Person einladen</h3>
                <p class="mt-1 text-xs text-frost-500">
                    Erstelle einen Link und schicke ihn weiter. Der Link gilt
                    {{ \App\Models\Invitation::VALID_DAYS }} Tage und kann genau einmal verwendet werden.
                </p>
            </div>

            <button type="button" wire:click="createInvitation" class="btn btn-primary w-full"
                    wire:loading.attr="disabled" wire:target="createInvitation">
                <x-icon name="link" class="h-5 w-5" />
                Einladungslink erstellen
            </button>

            @if ($inviteLink)
                <div x-data="{ copied: false, link: @js($inviteLink) }" class="space-y-2">
                    <label class="label" for="inviteLink">Einladungslink</label>

                    <div class="flex gap-2">
                        <input id="inviteLink" type="text" readonly value="{{ $inviteLink }}"
                               onfocus="this.select()" class="input text-xs">

                        <button type="button" class="btn btn-secondary shrink-0"
                                @click="navigator.clipboard?.writeText(link); copied = true; setTimeout(() => copied = false, 2000)">
                            <x-icon name="copy" class="h-4 w-4" />
                            <span x-text="copied ? 'Kopiert' : 'Kopieren'">Kopieren</span>
                        </button>
                    </div>

                    <p class="text-xs text-amber-700">
                        Der Link wird nur jetzt angezeigt – er ist nirgendwo gespeichert.
                    </p>
                </div>
            @endif
        </section>
    @endif

    {{-- Members --}}
    <section class="card p-4">
        <h3 class="text-sm font-semibold text-frost-900">
            Wer hat Zugriff
            <span class="badge badge-neutral ml-1">{{ $memberships->count() }}</span>
        </h3>

        <div class="mt-2 divide-y divide-frost-100">
            @foreach ($memberships as $membership)
                <div class="flex items-center gap-3 py-2.5" wire:key="member-{{ $membership->id }}">
                    <x-avatar :user="$membership->user" />

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-frost-900">
                            {{ $membership->user?->displayName() ?? 'Unbekannt' }}
                        </p>
                        <p class="truncate text-xs text-frost-500">
                            {{ $membership->user?->email }}
                        </p>
                    </div>

                    @if ($membership->isOwner())
                        <span class="badge badge-ok">Besitzer</span>
                    @elseif ($isOwner)
                        <button type="button" wire:click="removeMember({{ $membership->user_id }})"
                                wire:confirm="Zugriff für {{ $membership->user?->displayName() }} wirklich entziehen?"
                                class="btn btn-ghost btn-icon text-red-600" aria-label="Mitglied entfernen">
                            <x-icon name="close" class="h-5 w-5" />
                        </button>
                    @else
                        <span class="badge badge-neutral">Mitglied</span>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    {{-- Open invitations --}}
    @if ($isOwner && $openInvitations->isNotEmpty())
        <section class="card p-4">
            <h3 class="text-sm font-semibold text-frost-900">Offene Einladungen</h3>

            <div class="mt-2 divide-y divide-frost-100">
                @foreach ($openInvitations as $invitation)
                    <div class="flex items-center gap-3 py-2.5" wire:key="invitation-{{ $invitation->id }}">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-control bg-frost-100 text-frost-500">
                            <x-icon name="link" class="h-5 w-5" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-frost-900">
                                Gültig bis {{ $invitation->expires_at?->translatedFormat('d.m.Y, H:i') }}
                            </p>
                            <p class="truncate text-xs text-frost-500">Noch nicht verwendet</p>
                        </div>

                        <button type="button" wire:click="revokeInvitation({{ $invitation->id }})"
                                wire:confirm="Einladung zurückziehen?"
                                class="btn btn-ghost btn-icon text-red-600" aria-label="Einladung zurückziehen">
                            <x-icon name="trash" class="h-5 w-5" />
                        </button>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Leaving or deleting --}}
    <section class="card space-y-3 p-4">
        <h3 class="text-sm font-semibold text-frost-900">Aufräumen</h3>

        @if ($isOwner)
            <p class="text-xs text-frost-500">
                Beim Löschen verschwinden der Gefrierschrank, alle Schubladen und alle Artikel für immer.
            </p>

            <button type="button" wire:click="deleteFreezer"
                    wire:confirm="„{{ $freezer->name }}“ mit allen Artikeln löschen?"
                    class="btn btn-danger w-full">
                <x-icon name="trash" class="h-5 w-5" />
                Gefrierschrank löschen
            </button>
        @else
            <p class="text-xs text-frost-500">
                Du kannst den Gefrierschrank jederzeit verlassen – dein Zugriff wird dann entfernt.
            </p>

            <button type="button" wire:click="leaveFreezer"
                    wire:confirm="„{{ $freezer->name }}“ verlassen?"
                    class="btn btn-danger w-full">
                <x-icon name="logout" class="h-5 w-5" />
                Gefrierschrank verlassen
            </button>
        @endif
    </section>
</div>
