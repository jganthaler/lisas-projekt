<div class="space-y-4">
    <div class="flex items-center gap-3">
        <a href="{{ route('freezer.closed') }}" wire:navigate class="btn btn-ghost btn-icon"
           aria-label="{{ __('freezer.close_aria') }}">
            <x-icon name="arrow-left" class="h-5 w-5" />
        </a>

        <div class="min-w-0 flex-1">
            <h2 class="truncate text-base font-bold text-frost-900">{{ __('freezer.back') }}</h2>
            <p class="truncate text-xs text-frost-500">
                {{ $freezer->name }} · {{ trans_choice('common.drawers', $drawers->count()) }}
            </p>
        </div>
    </div>

    <x-notice :message="$feedback" />

    {{-- New drawer --}}
    <form wire:submit="addDrawer" class="card flex items-start gap-2 p-3">
        <div class="flex-1">
            <label class="label" for="newDrawerName">{{ __('freezer.new_drawer') }}</label>
            <input id="newDrawerName" type="text" wire:model="newDrawerName" placeholder="{{ __('freezer.new_drawer_placeholder') }}"
                   class="input @error('newDrawerName') input-error @enderror">
            @error('newDrawerName')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary btn-icon mt-6" aria-label="{{ __('freezer.add_drawer_aria') }}">
            <x-icon name="plus" class="h-5 w-5" />
        </button>
    </form>

    @error('drawerDeletion')
        <x-notice :message="$message" variant="error" />
    @enderror

    <section class="space-y-2">
        @forelse ($drawers as $drawer)
            @php
                $stats = $summary[$drawer->id] ?? ['items' => 0, 'warnings' => 0];
            @endphp

            @if ($editingDrawerId === $drawer->id)
                <form wire:submit="renameDrawer" class="card space-y-3 p-3" wire:key="rename-{{ $drawer->id }}">
                    <div>
                        <label class="label" for="editingDrawerName">{{ __('freezer.rename_drawer_label') }}</label>
                        <input id="editingDrawerName" type="text" wire:model="editingDrawerName"
                               wire:keydown.escape="cancelRenaming"
                               class="input @error('editingDrawerName') input-error @enderror">
                        @error('editingDrawerName')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-primary flex-1">
                            <x-icon name="check" class="h-5 w-5" />
                            {{ __('common.save') }}
                        </button>
                        <button type="button" wire:click="cancelRenaming" class="btn btn-ghost">{{ __('common.cancel') }}</button>
                    </div>
                </form>
            @else
                <div class="drawer-row" wire:key="drawer-{{ $drawer->id }}">
                    <a href="{{ route('drawers.show', $drawer) }}" wire:navigate
                       class="flex min-w-0 flex-1 items-center gap-3">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-control bg-frost-100 text-frost-600">
                            <x-icon name="list" class="h-5 w-5" />
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-frost-900">{{ $drawer->name }}</span>
                            <span class="block text-xs text-frost-500">{{ trans_choice('common.items', $stats['items']) }}</span>
                        </span>

                        @if ($stats['warnings'] > 0)
                            <span class="badge badge-warn">{{ $stats['warnings'] }}</span>
                        @endif
                    </a>

                    <button type="button" wire:click="startRenaming({{ $drawer->id }})"
                            class="btn btn-ghost btn-icon" aria-label="{{ __('freezer.rename_drawer_aria') }}">
                        <x-icon name="pencil" class="h-5 w-5" />
                    </button>

                    <button type="button"
                            wire:click="$dispatch('confirm-dialog', { name: 'drawer-delete-{{ $drawer->id }}' })"
                            class="btn btn-ghost btn-icon text-danger-600 dark:text-danger-400" aria-label="{{ __('freezer.delete_drawer_aria') }}">
                        <x-icon name="trash" class="h-5 w-5" />
                    </button>

                    <x-confirm-dialog
                        :name="'drawer-delete-'.$drawer->id"
                        :title="__('freezer.delete_drawer_confirm_title')"
                        :message="__('freezer.delete_drawer_confirm_message', ['name' => $drawer->name])"
                        :action="'deleteDrawer('.$drawer->id.')'" />
                </div>
            @endif
        @empty
            <div class="card px-4 py-10 text-center">
                <x-icon name="list" class="mx-auto h-8 w-8 text-frost-300" />
                <p class="mt-2 text-sm font-medium text-frost-700">{{ __('freezer.no_drawers') }}</p>
            </div>
        @endforelse
    </section>
</div>
