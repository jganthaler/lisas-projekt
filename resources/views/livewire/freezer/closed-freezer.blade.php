<div class="space-y-4">
    {{-- The freezer as you know it from the kitchen: closed. --}}
    <section class="card overflow-hidden">
        <div class="flex flex-col items-center gap-4 px-6 py-8 text-center">
            <span class="grid h-20 w-20 place-items-center rounded-control bg-brand-600 text-white shadow-lg shadow-brand-600/25">
                <x-icon name="freezer" class="h-10 w-10" />
            </span>

            <div>
                <h2 class="text-lg font-bold text-frost-900">{{ $freezer->name }}</h2>
                <p class="mt-1 text-sm text-frost-500">
                    @if ($itemCount === 0)
                        {{ __('freezer.empty') }}
                    @else
                        {{ __('freezer.summary', [
                            'items' => trans_choice('common.items', $itemCount),
                            'drawers' => trans_choice('common.drawers', $drawers->count()),
                        ]) }}
                    @endif
                </p>
            </div>
        </div>
    </section>

    {{-- All freezers of the household: switch between them, rename them and add new ones --}}
    <section class="card p-1">
        <div class="flex items-center gap-2 px-3 pb-1 pt-3">
            <h3 class="text-sm font-semibold text-frost-700">{{ __('freezer.mine') }}</h3>

            @if ($addingFreezer)
                <button type="button" wire:click="cancelAddingFreezer" class="btn btn-ghost btn-icon ml-auto"
                        aria-label="{{ __('freezer.add_cancel_aria') }}">
                    <x-icon name="close" class="h-5 w-5" />
                </button>
            @else
                <button type="button" wire:click="startAddingFreezer" class="btn btn-ghost btn-icon ml-auto"
                        aria-label="{{ __('freezer.add_aria') }}">
                    <x-icon name="plus" class="h-5 w-5" />
                </button>
            @endif
        </div>

        @if ($addingFreezer)
            {{-- Another freezer, e.g. the chest freezer in the cellar --}}
            <form wire:submit="addFreezer" class="space-y-3 p-3" wire:key="new-freezer">
                <div>
                    <label class="label" for="newFreezerName">{{ __('freezer.new_label') }}</label>
                    <input id="newFreezerName" type="text" wire:model="newFreezerName"
                           wire:keydown.escape="cancelAddingFreezer" placeholder="{{ __('freezer.new_placeholder') }}"
                           class="input @error('newFreezerName') input-error @enderror">
                    @error('newFreezerName')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary flex-1">
                        <x-icon name="check" class="h-5 w-5" />
                        {{ __('common.create') }}
                    </button>
                    <button type="button" wire:click="cancelAddingFreezer" class="btn btn-ghost">{{ __('common.cancel') }}</button>
                </div>
            </form>
        @endif

        @foreach ($freezers as $option)
            @if ($editingFreezerId === $option->id)
                <form wire:submit="renameFreezer" class="space-y-3 p-3"
                      wire:key="rename-freezer-{{ $option->id }}">
                    <div>
                        <label class="label" for="editingFreezerName">{{ __('freezer.rename_label') }}</label>
                        <input id="editingFreezerName" type="text" wire:model="editingFreezerName"
                               wire:keydown.escape="cancelRenaming"
                               class="input @error('editingFreezerName') input-error @enderror">
                        @error('editingFreezerName')
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
                <div class="flex items-center gap-1 px-1" wire:key="freezer-{{ $option->id }}">
                    <button type="button" wire:click="selectFreezer({{ $option->id }})"
                            class="flex min-w-0 flex-1 items-center gap-3 rounded-control px-2 py-2.5 text-left transition hover:bg-frost-50">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-control {{ $option->is($freezer) ? 'bg-brand-600 text-white' : 'bg-frost-100 text-frost-500' }}">
                            <x-icon name="freezer" class="h-5 w-5" />
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-frost-900">{{ $option->name }}</span>
                            <span class="block text-xs text-frost-500">
                                {{ $option->isOwnedBy(auth()->user()) ? __('freezer.owned') : __('freezer.shared') }}
                                @if ($option->is($freezer))
                                    · {{ __('freezer.active') }}
                                @endif
                            </span>
                        </span>

                        @if ($option->is($freezer))
                            <x-icon name="check" class="h-5 w-5 shrink-0 text-brand-600" />
                        @endif
                    </button>

                    @if ($option->isOwnedBy(auth()->user()))
                        <button type="button" wire:click="startRenaming({{ $option->id }})"
                                class="btn btn-ghost btn-icon" aria-label="{{ __('freezer.rename_aria') }}">
                            <x-icon name="pencil" class="h-5 w-5" />
                        </button>

                        <button type="button"
                                wire:click="$dispatch('confirm-dialog', { name: 'freezer-delete-{{ $option->id }}' })"
                                class="btn btn-ghost btn-icon text-danger-600 dark:text-danger-400"
                                aria-label="{{ __('freezer.delete_aria') }}">
                            <x-icon name="trash" class="h-5 w-5" />
                        </button>

                        <x-confirm-dialog
                            :name="'freezer-delete-'.$option->id"
                            :title="__('freezer.delete_confirm_title')"
                            :message="__('freezer.delete_confirm_message', ['name' => $option->name])"
                            :action="'deleteFreezer('.$option->id.')'"
                            :confirm-label="__('common.delete_forever')" />
                    @endif
                </div>
            @endif
        @endforeach
    </section>

    @if ($drawers->isNotEmpty())
        <section class="space-y-2">
            <h3 class="px-1 text-sm font-semibold text-frost-700">{{ __('freezer.drawers_heading') }}</h3>

            @foreach ($drawers as $drawer)
                @php
                    $stats = $summary[$drawer->id] ?? ['items' => 0, 'warnings' => 0];
                @endphp

                {{-- A look into the drawer: what lies inside, alphabetically one
                     below the other, and only the name and the amount. Changed is
                     something in the drawer itself, so the whole card stays the
                     way in - there is nothing to edit here. --}}
                <a href="{{ route('drawers.show', $drawer) }}" wire:navigate
                   class="drawer-row items-start" wire:key="drawer-{{ $drawer->id }}">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-control bg-frost-100 text-frost-600">
                        <x-icon name="list" class="h-5 w-5" />
                    </span>

                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-semibold text-frost-900">{{ $drawer->name }}</span>

                        @if ($drawer->items->isEmpty())
                            <span class="mt-1 block text-xs text-frost-500">{{ __('drawers.empty') }}</span>
                        @else
                            {{-- The items arrive sorted (see Drawer::items()), so the
                                 list needs no order of its own. --}}
                            <ul class="mt-1 space-y-1">
                                @foreach ($drawer->items as $item)
                                    <li class="flex items-baseline justify-between gap-2 text-xs">
                                        <span class="min-w-0 truncate text-frost-700">{{ $item->name }}</span>
                                        <span class="shrink-0 font-medium text-frost-500">{{ $item->quantity }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </span>

                    @if ($stats['warnings'] > 0)
                        <span class="badge badge-warn">{{ $stats['warnings'] }}</span>
                    @endif

                    <x-icon name="chevron-right" class="h-5 w-5 shrink-0 text-frost-400" />
                </a>
            @endforeach
        </section>
    @endif

    {{-- Managing the drawers sits at the very bottom of the page: the freezer is
         opened to look something up far more often than it is rearranged. --}}
    <a href="{{ route('freezer.open') }}" wire:navigate class="btn btn-primary w-full">
        <x-icon name="pencil" class="h-5 w-5" />
        {{ __('freezer.manage_drawers') }}
    </a>
</div>
