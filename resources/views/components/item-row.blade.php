@props(['item', 'drawerName' => null, 'freezerName' => null, 'mhdWarningsOnly' => false])

{{--
 | One item of a list that stands outside a drawer: the inventory overview and
 | the MHD watch list. The buttons call the actions of the surrounding Livewire
 | component (see App\Livewire\Concerns\InteractsWithItems), so both read the
 | same row.
 |
 | The row reads like the label on a box, three lines long:
 |
 |     Tiefkühlpizza
 |     MHD: 15.12.2026
 |     Gefrierschrank 1, Schublade 2
 |
 | The second line names the best before date with the label of the field
 | (common.best_before, "MHD") - the same wording the row in the drawer uses (see
 | components/drawer-item-row.blade.php). An item without a date says so at that
 | very place ("Kein MHD", items.mhd.none): the place of the date is never left
 | empty, and neither the state badge nor the relative counter comes along,
 | because there is nothing to warn about.
 |
 | The third line names the place of the item: the freezer and the drawer with a
 | comma between them, both as plain text - the same size and the same colour, no
 | link under either of them. The freezer only appears where it tells rows apart
 | (see the callers) - with a single freezer the drawer alone says where the item
 | lies. The way into the drawer is the menu on the right: its "edit" and "move"
 | entries hand the item over with "?item=<id>", so the drawer opens with the form
 | of this very item (see DrawerPage::mount()).
 |
 | The second line carries the date alone. Where the row stands in a list that
 | warns - the inventory overview reads it with "mhdWarningsOnly" - the state
 | comes along as long as it warns ("abgelaufen", "bald abgelaufen"); "frisch"
 | says nothing where the date stands next to it, and a relative counter ("MHD
 | in 3 Tagen") stands in no row of the app: the date is the answer, and the MHD
 | watch list names the state in the headings of its sections ("Abgelaufen",
 | "Bald abgelaufen" - see expiring-items).
 |
 | The card is white in every list: the category of an item serves the filter of
 | the inventory (see InventoryOverview), it is no colour of a row.
 |
 | On the right the quantity keeps its place - one tap for minus, one for plus -
 | and next to it the menu ("⋮") carries the three actions of the row: editing,
 | deleting and moving. Editing and moving are the functions of the drawer (the
 | row leads into it with the item it names; the way in is the menu, never the
 | name of the drawer); deleting asks through the dialog of the app
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
                 field. An item without a date says so instead - the place of
                 the date is never left empty (see items.mhd.none), and the
                 badge stays away with it. --}}
            <p class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-frost-500">
                @if ($item->best_before)
                    <span>{{ __('common.best_before') }}: {{ $item->formattedBestBefore() }}</span>

                    {{-- A warning stands beside the date while it warns, and only
                         in the list that reads the row with "mhdWarningsOnly":
                         "frisch" says nothing where the date stands next to it,
                         and a relative counter stands in no row of the app. --}}
                    @if ($mhdWarningsOnly && ($item->isExpired() || $item->isExpiringSoon()))
                        <span class="badge {{ $item->stateBadgeClass() }}">{{ $item->stateLabel() }}</span>
                    @endif
                @else
                    <span>{{ __('items.mhd.none') }}</span>
                @endif
            </p>

            {{-- The third line: the place of the item, both names as plain text.
                 The comma belongs to the freezer - it only stands there while a
                 drawer follows. Nothing here is clickable: the way into the
                 drawer is the menu on the right (see below). The two names are
                 echoes, never directives: a directive would leave one of the
                 comments of Livewire between them, and the line would read
                 "Gefrierschrank 1<!--[if BLOCK]><![endif]-->, Schublade 2". --}}
            @if ($freezerName || $drawerName)
                <p class="mt-0.5 truncate text-xs text-frost-500">{{ $freezerName }}{{ $freezerName && $drawerName ? ', ' : '' }}{{ $drawerName }}</p>
            @endif
        </div>

        <div class="flex shrink-0 items-center gap-1">
            {{-- The quantity keeps its place - one tap for minus, one for plus -
                 and steps to the left, because the menu takes the right end of
                 the row. --}}
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
                 Editing and moving are the functions of the drawer, so both
                 entries lead into it with this very item - "&move=1" asks for
                 the move form instead of the edit form (see
                 DrawerPage::mount()). The menu names the item it belongs to, so
                 a screen reader does not read a bare "⋮" next to every entry of
                 the list. --}}
            <x-menu :label="__('items.menu_aria', ['name' => $item->name])">
                @if ($item->drawer)
                    <a href="{{ route('drawers.show', $item->drawer) }}?item={{ $item->getKey() }}"
                       wire:navigate class="menu-item" role="menuitem">
                        <x-icon name="pencil" class="h-4 w-4" />
                        {{ __('common.edit') }}
                    </a>
                @endif

                <button type="button"
                        wire:click="$dispatch('confirm-dialog', { name: 'item-delete-{{ $item->id }}' })"
                        class="menu-item menu-item-danger" role="menuitem">
                    <x-icon name="trash" class="h-4 w-4" />
                    {{ __('common.delete') }}
                </button>

                @if ($item->drawer)
                    <a href="{{ route('drawers.show', $item->drawer) }}?item={{ $item->getKey() }}&amp;move=1"
                       wire:navigate class="menu-item" role="menuitem">
                        <x-icon name="move" class="h-4 w-4" />
                        {{ __('common.move') }}
                    </a>
                @endif
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
