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

        <button type="submit" class="btn h-11 w-11 shrink-0 btn-primary btn-icon mt-6" aria-label="{{ __('freezer.add_drawer_aria') }}">
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

                    {{-- „Abbrechen“ links, „Speichern“ rechts - dasselbe Paar
                         wie in jedem Formular der App, in zwei gleich breiten
                         Spalten (siehe drawer-page.blade.php, das Formular eines
                         Artikels). --}}
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" wire:click="cancelRenaming" class="btn btn-ghost w-full">
                            {{ __('common.cancel') }}
                        </button>

                        <button type="submit" class="btn btn-primary w-full">
                            <x-icon name="check" class="h-5 w-5" />
                            {{ __('common.save') }}
                        </button>
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

                    {{-- Everything that changes the drawer itself sits behind its
                         menu: the row keeps the name and the count, and both
                         actions still call the functions of this page - renaming
                         opens the form of the drawer, deleting asks through the
                         dialog of the app (see components/menu.blade.php). --}}
                    <x-menu :label="__('freezer.drawer_menu_aria', ['name' => $drawer->name])">
                        <button type="button" wire:click="startRenaming({{ $drawer->id }})"
                                class="menu-item" role="menuitem">
                            <x-icon name="pencil" class="h-4 w-4" />
                            {{ __('freezer.rename_drawer') }}
                        </button>

                        <button type="button"
                                wire:click="$dispatch('confirm-dialog', { name: 'drawer-delete-{{ $drawer->id }}' })"
                                class="menu-item menu-item-danger" role="menuitem">
                            <x-icon name="trash" class="h-4 w-4" />
                            {{ __('common.delete') }}
                        </button>
                    </x-menu>

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
