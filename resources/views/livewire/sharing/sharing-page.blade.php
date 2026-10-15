<div class="space-y-4">
    <div>
        <h2 class="text-base font-bold text-frost-900">{{ __('sharing.title') }}</h2>
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
            <h3 class="px-3 pb-1 pt-3 text-sm font-semibold text-frost-700">{{ __('freezer.mine') }}</h3>

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
                            {{ $option->isOwnedBy(auth()->user()) ? __('freezer.owned') : __('freezer.shared') }}
                        </span>
                    </span>

                    @if ($option->is($freezer))
                        <x-icon name="check" class="h-5 w-5 shrink-0 text-brand-600" />
                    @endif
                </button>
            @endforeach
        </section>
    @endif

    {{-- Invite somebody new --}}
    @if ($isOwner)
        <section class="card space-y-3 p-4">
            <div>
                <h3 class="text-sm font-semibold text-frost-900">{{ __('sharing.invite_heading') }}</h3>
                <p class="mt-1 text-xs text-frost-500">
                    {{ __('sharing.invite_hint', ['days' => \App\Models\Invitation::VALID_DAYS]) }}
                </p>
            </div>

            <button type="button" wire:click="createInvitation" class="btn btn-primary w-full"
                    wire:loading.attr="disabled" wire:target="createInvitation">
                <x-icon name="link" class="h-5 w-5" />
                {{ __('sharing.create_link') }}
            </button>

            @if ($inviteLink)
                <div x-data="{ state: 'idle', link: @js($inviteLink) }" class="space-y-2">
                    <label class="label" for="inviteLink">{{ __('sharing.link_label') }}</label>

                    <div class="flex gap-2">
                        <input id="inviteLink" type="text" readonly value="{{ $inviteLink }}"
                               onfocus="this.select()" class="input text-xs">

                        {{--
                         | Copying runs through window.copyToClipboard (see
                         | resources/js/app.js): the clipboard API of the browser only
                         | exists on a secure origin, otherwise the old copy command is
                         | used. The label tells the user which of the two happened.
                        --}}
                        <button type="button" class="btn btn-secondary shrink-0"
                                x-on:click="
                                    if (state !== 'idle') {
                                        return;
                                    }

                                    state = 'busy';

                                    window.copyToClipboard(link).then((ok) => {
                                        state = ok ? 'copied' : 'failed';

                                        setTimeout(() => state = 'idle', 2500);
                                    });
                                ">
                            <x-icon name="copy" class="h-4 w-4" x-show="state !== 'copied'" />
                            <x-icon name="check" class="h-4 w-4" x-cloak x-show="state === 'copied'" />

                            <span x-show="state === 'idle'">{{ __('common.copy') }}</span>
                            <span x-cloak x-show="state === 'busy'">{{ __('common.busy') }}</span>
                            <span x-cloak x-show="state === 'copied'">{{ __('common.copied') }}</span>
                            <span x-cloak x-show="state === 'failed'">{{ __('common.copy_failed') }}</span>
                        </button>
                    </div>

                    <p class="text-xs text-warning-700 dark:text-warning-300">
                        {{ __('sharing.link_hint') }}
                    </p>
                </div>
            @endif
        </section>
    @endif

    {{-- Members --}}
    <section class="card p-4">
        <h3 class="text-sm font-semibold text-frost-900">
            {{ __('sharing.members_heading') }}
            <span class="badge badge-neutral ml-1">{{ $memberships->count() }}</span>
        </h3>

        <div class="mt-2 divide-y divide-frost-100">
            @foreach ($memberships as $membership)
                <div class="flex items-center gap-3 py-2.5" wire:key="member-{{ $membership->id }}">
                    <x-avatar :user="$membership->user" />

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-frost-900">
                            {{ $membership->user?->displayName() ?? __('common.unknown') }}
                        </p>
                        <p class="truncate text-xs text-frost-500">
                            {{ $membership->user?->email }}
                        </p>
                    </div>

                    @if ($membership->isOwner())
                        <span class="badge badge-ok">{{ __('sharing.owner') }}</span>
                    @elseif ($isOwner)
                        <button type="button"
                                wire:click="$dispatch('confirm-dialog', { name: 'member-remove-{{ $membership->id }}' })"
                                class="btn btn-ghost btn-icon text-danger-600 dark:text-danger-400" aria-label="{{ __('sharing.remove_member_aria') }}">
                            <x-icon name="close" class="h-5 w-5" />
                        </button>

                        <x-confirm-dialog
                            :name="'member-remove-'.$membership->id"
                            :title="__('sharing.revoke_title')"
                            :message="__('sharing.revoke_message', ['name' => $membership->user?->displayName() ?? __('common.this_person')])"
                            :action="'removeMember('.$membership->user_id.')'"
                            :confirm-label="__('sharing.revoke_confirm')"
                            icon="close" />
                    @else
                        <span class="badge badge-neutral">{{ __('sharing.member') }}</span>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    {{-- Open invitations --}}
    @if ($isOwner && $openInvitations->isNotEmpty())
        <section class="card p-4">
            <h3 class="text-sm font-semibold text-frost-900">{{ __('sharing.open_invitations') }}</h3>

            <div class="mt-2 divide-y divide-frost-100">
                @foreach ($openInvitations as $invitation)
                    <div class="flex items-center gap-3 py-2.5" wire:key="invitation-{{ $invitation->id }}">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-control bg-frost-100 text-frost-500">
                            <x-icon name="link" class="h-5 w-5" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-frost-900">
                                {{ __('sharing.valid_until', ['date' => $invitation->expires_at?->translatedFormat('d.m.Y, H:i')]) }}
                            </p>
                            <p class="truncate text-xs text-frost-500">{{ __('sharing.not_used') }}</p>
                        </div>

                        <button type="button"
                                wire:click="$dispatch('confirm-dialog', { name: 'invitation-revoke-{{ $invitation->id }}' })"
                                class="btn btn-ghost btn-icon text-danger-600 dark:text-danger-400" aria-label="{{ __('sharing.revoke_invitation_aria') }}">
                            <x-icon name="trash" class="h-5 w-5" />
                        </button>

                        <x-confirm-dialog
                            :name="'invitation-revoke-'.$invitation->id"
                            :title="__('sharing.revoke_invitation_title')"
                            :message="__('sharing.revoke_invitation_message')"
                            :action="'revokeInvitation('.$invitation->id.')'"
                            :confirm-label="__('sharing.revoke_invitation_confirm')" />
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Leaving or deleting --}}
    <section class="card space-y-3 p-4">
        <h3 class="text-sm font-semibold text-frost-900">{{ __('sharing.cleanup') }}</h3>

        @if ($isOwner)
            <p class="text-xs text-frost-500">
                {{ __('sharing.delete_hint') }}
            </p>

            <button type="button"
                    wire:click="$dispatch('confirm-dialog', { name: 'freezer-delete' })"
                    class="btn btn-danger w-full">
                <x-icon name="trash" class="h-5 w-5" />
                {{ __('sharing.delete_freezer') }}
            </button>

            <x-confirm-dialog
                name="freezer-delete"
                :title="__('freezer.delete_confirm_title')"
                :message="__('freezer.delete_confirm_message', ['name' => $freezer->name])"
                action="deleteFreezer"
                :confirm-label="__('common.delete_forever')" />
        @else
            <p class="text-xs text-frost-500">
                {{ __('sharing.leave_hint') }}
            </p>

            <button type="button"
                    wire:click="$dispatch('confirm-dialog', { name: 'freezer-leave' })"
                    class="btn btn-danger w-full">
                <x-icon name="logout" class="h-5 w-5" />
                {{ __('sharing.leave_freezer') }}
            </button>

            <x-confirm-dialog
                name="freezer-leave"
                :title="__('sharing.leave_title')"
                :message="__('sharing.leave_message', ['name' => $freezer->name])"
                action="leaveFreezer"
                :confirm-label="__('sharing.leave_confirm')"
                icon="logout" />
        @endif
    </section>
</div>
