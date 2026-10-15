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
                        Hier ist noch nichts eingefroren.
                    @else
                        {{ $itemCount }} Artikel in {{ $drawers->count() }} Schubladen
                    @endif
                </p>
            </div>
        </div>
    </section>

    {{-- All freezers of the household: switch between them, rename them and add new ones --}}
    <section class="card p-1">
        <div class="flex items-center gap-2 px-3 pb-1 pt-3">
            <h3 class="text-sm font-semibold text-frost-700">Meine Gefrierschränke</h3>

            @if ($addingFreezer)
                <button type="button" wire:click="cancelAddingFreezer" class="btn btn-ghost btn-icon ml-auto"
                        aria-label="Anlegen abbrechen">
                    <x-icon name="close" class="h-5 w-5" />
                </button>
            @else
                <button type="button" wire:click="startAddingFreezer" class="btn btn-ghost btn-icon ml-auto"
                        aria-label="Gefrierschrank hinzufügen">
                    <x-icon name="plus" class="h-5 w-5" />
                </button>
            @endif
        </div>

        @if ($addingFreezer)
            {{-- Another freezer, e.g. the chest freezer in the cellar --}}
            <form wire:submit="addFreezer" class="space-y-3 p-3" wire:key="new-freezer">
                <div>
                    <label class="label" for="newFreezerName">Neuer Gefrierschrank</label>
                    <input id="newFreezerName" type="text" wire:model="newFreezerName"
                           wire:keydown.escape="cancelAddingFreezer" placeholder="z. B. Truhe im Keller"
                           class="input @error('newFreezerName') input-error @enderror">
                    @error('newFreezerName')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary flex-1">
                        <x-icon name="check" class="h-5 w-5" />
                        Anlegen
                    </button>
                    <button type="button" wire:click="cancelAddingFreezer" class="btn btn-ghost">Abbrechen</button>
                </div>
            </form>
        @endif

        @foreach ($freezers as $option)
            @if ($editingFreezerId === $option->id)
                <form wire:submit="renameFreezer" class="space-y-3 p-3"
                      wire:key="rename-freezer-{{ $option->id }}">
                    <div>
                        <label class="label" for="editingFreezerName">Name des Gefrierschranks</label>
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
                            Speichern
                        </button>
                        <button type="button" wire:click="cancelRenaming" class="btn btn-ghost">Abbrechen</button>
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
                                {{ $option->isOwnedBy(auth()->user()) ? 'Dein Gefrierschrank' : 'Mit dir geteilt' }}
                                @if ($option->is($freezer))
                                    · aktiv
                                @endif
                            </span>
                        </span>

                        @if ($option->is($freezer))
                            <x-icon name="check" class="h-5 w-5 shrink-0 text-brand-600" />
                        @endif
                    </button>

                    @if ($option->isOwnedBy(auth()->user()))
                        <button type="button" wire:click="startRenaming({{ $option->id }})"
                                class="btn btn-ghost btn-icon" aria-label="Gefrierschrank umbenennen">
                            <x-icon name="pencil" class="h-5 w-5" />
                        </button>

                        <button type="button"
                                wire:click="$dispatch('confirm-dialog', { name: 'freezer-delete-{{ $option->id }}' })"
                                class="btn btn-ghost btn-icon text-red-600 dark:text-red-400"
                                aria-label="Gefrierschrank löschen">
                            <x-icon name="trash" class="h-5 w-5" />
                        </button>

                        <x-confirm-dialog
                            :name="'freezer-delete-'.$option->id"
                            title="Gefrierschrank löschen?"
                            :message="'„'.$option->name.'“ verschwindet mit allen Schubladen und Artikeln für immer.'"
                            :action="'deleteFreezer('.$option->id.')'"
                            confirm-label="Endgültig löschen" />
                    @endif
                </div>
            @endif
        @endforeach
    </section>

    @if ($drawers->isNotEmpty())
        <section class="space-y-2">
            <h3 class="px-1 text-sm font-semibold text-frost-700">Schubladen</h3>

            @foreach ($drawers as $drawer)
                @php
                    $stats = $summary[$drawer->id] ?? ['items' => 0, 'warnings' => 0];
                @endphp

                <a href="{{ route('drawers.show', $drawer) }}" wire:navigate
                   class="drawer-row" wire:key="drawer-{{ $drawer->id }}">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-control bg-frost-100 text-frost-600">
                        <x-icon name="list" class="h-5 w-5" />
                    </span>

                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-semibold text-frost-900">{{ $drawer->name }}</span>
                        <span class="block text-xs text-frost-500">{{ $stats['items'] }} Artikel</span>
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
        Schubladen bearbeiten
    </a>
</div>
