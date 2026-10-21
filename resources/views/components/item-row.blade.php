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
 | comma between them. The freezer only appears where it tells rows apart (see
 | the callers) - with a single freezer the drawer alone says where the item
 | lies. The name of the drawer is the link into that drawer: the row hands the
 | item over with "?item=<id>", so the drawer opens with the form of this very
 | item (see DrawerPage::mount()).
 |
 | The best before date has two shapes. The full one names the state ("frisch",
 | "bald abgelaufen", ...), the relative counter ("MHD in 3 Tagen") and the date
 | itself; the inventory overview reads the row with "mhdWarningsOnly" and keeps
 | only what carries information there - the date, and the state as long as it
 | warns. "frisch" and the counter stay on the page that lives from them (the
 | MHD watch list).
 |
 | The card is white in every list: the category of an item serves the filter of
 | the inventory (see InventoryOverview), it is no colour of a row.
 |
 | On the right the quantity keeps its place - one tap for minus, one for plus -
 | and next to it the menu ("⋮") carries the three actions of the row: editing,
 | deleting and moving. Editing and moving are the functions of the drawer (the
 | row leads into it with the item it names, the way the name of the drawer
 | does); deleting asks through the dialog of the app
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
                 badge and the counter stay away with it. --}}
            <p class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-frost-500">
                @if ($item->best_before)
                    <span>{{ __('common.best_before') }}: {{ $item->formattedBestBefore() }}</span>

                    {{-- A warning is always shown, "frisch" only where the row
                         has room for it (see "mhdWarningsOnly"). --}}
                    @if (! $mhdWarningsOnly || $item->isExpired() || $item->isExpiringSoon())
                        <span class="badge {{ $item->stateBadgeClass() }}">{{ $item->stateLabel() }}</span>
                    @endif

                    {{-- The relative counter belongs to the page that lives from
                         it (see "mhdWarningsOnly"); the dot between the two
                         would hang in the air without it. --}}
                    @unless ($mhdWarningsOnly)
                        <span class="text-frost-400">·</span>
                        <span>{{ $item->bestBeforeLabel() }}</span>
                    @endunless
                @else
                    <span>{{ __('items.mhd.none') }}</span>
                @endif
            </p>

            {{-- The third line: the place of the item. The comma belongs to the
                 freezer - it only stands there while a drawer follows. --}}
            @if ($freezerName || $drawerName)
                <p class="mt-0.5 truncate text-xs text-frost-500">
                    @if ($freezerName)
                        {{ $freezerName }}{{ $drawerName ? ', ' : '' }}
                    @endif

                    @if ($drawerName && $item->drawer)
                        {{-- Where the item lies, as the way into it: the link
                             carries the accent of the app, so its purpose is
                             clear on a phone. --}}
                        <a href="{{ route('drawers.show', $item->drawer) }}?item={{ $item->getKey() }}"
                           wire:navigate
                           class="font-medium text-brand-700 underline-offset-2 transition hover:underline dark:text-brand-400"
                           aria-label="{{ __('items.open_in_drawer_aria', ['drawer' => $drawerName]) }}">{{ $drawerName }}</a>
                    @elseif ($drawerName)
                        {{ $drawerName }}
                    @endif
                </p>
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
