@props(['item', 'editable' => false, 'drawerName' => null, 'freezerName' => null, 'confirming' => false])

{{--
 | One item of the inventory. The buttons call the actions of the surrounding
 | Livewire component (see App\Livewire\Concerns\InteractsWithItems), so the row
 | can be reused by the drawer, the inventory overview and the MHD list.
 |
 | Deleting never opens the dialog of the browser: the trash button only arms
 | the question, which then appears inside the row (see confirmDelete()).
--}}
<div class="card p-3" wire:key="item-{{ $item->id }}">
    <div class="flex items-start gap-2">
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold text-frost-900">{{ $item->name }}</p>

            {{-- An item without a best before date, category and place shows no
                 meta information at all. --}}
            @if ($item->best_before || $item->category || $freezerName || $drawerName)
                <p class="mt-1 flex flex-wrap items-center gap-1.5 text-xs text-frost-500">
                    {{-- The category is a tag, not a place - hence its own colour. --}}
                    @if ($item->category)
                        <span class="badge badge-ok">{{ $item->categoryLabel() }}</span>
                    @endif

                    @if ($item->best_before)
                        <span class="badge {{ $item->stateBadgeClass() }}">{{ $item->stateLabel() }}</span>
                        <span>{{ $item->bestBeforeLabel() }}</span>
                        <span class="text-frost-400">·</span>
                        <span>{{ $item->formattedBestBefore() }}</span>
                    @endif
                    @if ($freezerName)
                        {{-- Names can get long, so the badge truncates instead of pushing the row apart. --}}
                        <span class="badge badge-neutral max-w-full">
                            <x-icon name="freezer" class="h-3.5 w-3.5 shrink-0" />
                            <span class="min-w-0 truncate">{{ $freezerName }}</span>
                        </span>
                    @endif
                    @if ($drawerName)
                        <span class="badge badge-neutral max-w-full">
                            <span class="min-w-0 truncate">{{ $drawerName }}</span>
                        </span>
                    @endif
                </p>
            @endif
        </div>

        <div class="flex shrink-0 items-center gap-1">
            @unless ($confirming)
                <button type="button" wire:click="confirmDelete({{ $item->id }})"
                        class="btn btn-ghost btn-icon text-red-600 dark:text-red-400" aria-label="Artikel löschen">
                    <x-icon name="trash" class="h-4 w-4" />
                </button>
            @endunless

            <div class="flex items-center rounded-control bg-frost-100">
                <button type="button" wire:click="decrementQuantity({{ $item->id }})"
                        class="btn btn-ghost btn-icon" aria-label="Menge verringern">
                    <x-icon name="minus" class="h-4 w-4" />
                </button>

                <span class="min-w-6 text-center text-sm font-semibold text-frost-900">{{ $item->quantity }}</span>

                <button type="button" wire:click="incrementQuantity({{ $item->id }})"
                        class="btn btn-ghost btn-icon" aria-label="Menge erhöhen">
                    <x-icon name="plus" class="h-4 w-4" />
                </button>
            </div>
        </div>
    </div>

    @if ($confirming)
        <div class="mt-2 flex flex-wrap items-center gap-2 border-t border-frost-100 pt-2">
            <p class="min-w-0 flex-1 text-sm font-medium text-frost-700">Möchtest du „{{ $item->name }}“ wirklich löschen?</p>

            <div class="flex gap-2">
                <button type="button" wire:click="deleteItem({{ $item->id }})" class="btn btn-danger">
                    <x-icon name="trash" class="h-4 w-4" />
                    Löschen
                </button>

                <button type="button" wire:click="cancelDelete" class="btn btn-ghost">Abbrechen</button>
            </div>
        </div>
    @elseif ($editable)
        <div class="mt-2 flex items-center justify-end gap-1 border-t border-frost-100 pt-2">
            <button type="button" wire:click="startEditingItem({{ $item->id }})"
                    class="btn btn-ghost btn-icon" aria-label="Artikel bearbeiten">
                <x-icon name="pencil" class="h-4 w-4" />
            </button>
        </div>
    @endif
</div>
