@props(['item'])

{{--
 | One item inside its drawer ("Gefrierschrank" -> "Inhalt").
 |
 | The row has two lines: the name of the item, and below it its best before
 | date with the label of the field ("MHD: 15.12.2026"). An item without a date
 | says so at that very place ("Kein MHD", items.mhd.none) - the place of the
 | date is never left empty. The drawer is opened
 | from its own name, so neither the freezer nor the drawer is named again
 | inside the row - the rows of the lists outside a drawer keep that meta line
 | (see components/item-row.blade.php).
 |
 | The card stays white - like every row of the app: the category of an item
 | serves the filter of the inventory (see InventoryOverview), it is no colour of
 | a row, and this list is read drawer by drawer, where the category of an item
 | rarely changes.
 |
 | On the right the quantity keeps its place - one tap for minus, one for plus -
 | and next to it the menu ("⋮") carries what used to stand as buttons below the
 | item: editing (the form replaces this row, see DrawerPage::startEditingItem()),
 | moving into another drawer or freezer (DrawerPage::startMovingItem()) and
 | deleting. Deleting asks through the dialog of the app
 | (components/confirm-dialog.blade.php), never through the dialog of the
 | browser: the entry in the menu only opens the question, and the answer travels
 | with the button inside that dialog.
 |
 | Every entry carries the id of its own item, so a tap can only ever touch the
 | item of the row it stands in.
--}}
<div class="card p-3" wire:key="item-{{ $item->id }}">
    <div class="flex items-center gap-2">
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold text-frost-900">{{ $item->name }}</p>

            {{-- The second line: the best before date with the label of the
                 field ("MHD: 15.12.2026") - or, without a date, the note that
                 there is none. The place of the date is never left empty. --}}
            <p class="mt-0.5 truncate text-xs text-frost-500">
                @if ($item->best_before)
                    <span>{{ __('common.best_before') }}: {{ $item->formattedBestBefore() }}</span>
                @else
                    <span>{{ __('items.mhd.none') }}</span>
                @endif
            </p>
        </div>

        <div class="flex shrink-0 items-center gap-1">
            <div class="flex items-center rounded-control bg-frost-100">
                <button type="button" wire:click="decrementQuantity({{ $item->id }})"
                        class="btn btn-ghost btn-icon" aria-label="{{ __('items.decrease_aria') }}">
                    <x-icon name="minus" class="h-4 w-4" />
                </button>

                <span class="min-w-6 text-center text-sm font-semibold text-frost-900">{{ $item->quantity }}</span>

                <button type="button" wire:click="incrementQuantity({{ $item->id }})"
                        class="btn btn-ghost btn-icon" aria-label="{{ __('items.increase_aria') }}">
                    <x-icon name="plus" class="h-4 w-4" />
                </button>
            </div>

            {{-- The three actions of this row, each with its icon and its label.
                 The menu names the item it belongs to, so a screen reader does
                 not read a bare "⋮" next to every entry of the list. --}}
            <x-menu :label="__('items.menu_aria', ['name' => $item->name])">
                <button type="button" wire:click="startEditingItem({{ $item->id }})"
                        class="menu-item" role="menuitem">
                    <x-icon name="pencil" class="h-4 w-4" />
                    {{ __('common.edit') }}
                </button>

                <button type="button"
                        wire:click="$dispatch('confirm-dialog', { name: 'item-delete-{{ $item->id }}' })"
                        class="menu-item menu-item-danger" role="menuitem">
                    <x-icon name="trash" class="h-4 w-4" />
                    {{ __('common.delete') }}
                </button>

                <button type="button" wire:click="startMovingItem({{ $item->id }})"
                        class="menu-item" role="menuitem">
                    <x-icon name="move" class="h-4 w-4" />
                    {{ __('common.move') }}
                </button>
            </x-menu>
        </div>
    </div>

    {{-- The question of this row. Every row carries its own dialog; the name
         keeps them apart (see components/confirm-dialog.blade.php). --}}
    <x-confirm-dialog
        :name="'item-delete-'.$item->id"
        :title="__('items.delete_confirm_title')"
        :message="__('items.delete_confirm_message', ['name' => $item->name])"
        :action="'deleteItem('.$item->id.')'" />
</div>
