@props(['item', 'editable' => false, 'drawerName' => null, 'freezerName' => null, 'mhdWarningsOnly' => false, 'categoryTint' => false])

{{--
 | One item of the inventory. The buttons call the actions of the surrounding
 | Livewire component (see App\Livewire\Concerns\InteractsWithItems), so the row
 | can be reused by the drawer, the inventory overview and the MHD list.
 |
 | Editing and moving are only offered where the surrounding component provides
 | them (the "editable" flag, see DrawerPage::startMovingItem()).
 |
 | The best before date has two shapes. The full one names the state ("frisch",
 | "bald abgelaufen", ...), the relative counter ("MHD in 3 Tagen") and the date
 | itself; the inventory overview reads the row with "mhdWarningsOnly" and keeps
 | only what carries information there - the date, and the state as long as it
 | warns. "frisch" and the counter stay on the pages that live from them (the
 | drawer and the MHD watch list).
 |
 | Only the inventory overview also reads the row with "categoryTint": it paints
 | the card in the colour of the category of the item (see
 | InventoryItem::categoryTintClass()), and the meta line of such a card steps
 | one shade darker so it keeps its contrast. The drawer and the MHD list keep
 | the plain, white card - they are read drawer by drawer, where the category of
 | an item rarely changes.
 |
 | Deleting always asks through the dialog of the app (see
 | components/confirm-dialog), never through the dialog of the browser: the trash
 | button only opens it, and the answer travels with the button inside it.
 |
 | The name of the drawer is a link wherever the row stands outside that drawer:
 | the inventory and the MHD watch list hand the item over with "?item=<id>", so
 | the drawer opens with the form of this very item (see DrawerPage::mount()).
 | Inside the drawer itself the name would lead to the page it stands on, so
 | there the badge stays a plain label.
--}}
@php
    /*
     | Null for "Sonstiges" and for an item without a category; "array_filter"
     | then drops it from the list, so the card keeps its white and the class
     | attribute stays the plain "card p-3".
     */
    $tintClass = $categoryTint ? $item->categoryTintClass() : null;

    /*
     | On a tinted card the meta line steps one shade darker: the muted grey of
     | the plain card would not carry 4.5:1 on a tint (see app.css).
     */
    $metaClass = $tintClass === null ? 'text-frost-500' : 'text-frost-700';
@endphp
<div @class(array_filter(['card', 'p-3', $tintClass])) wire:key="item-{{ $item->id }}">
    <div class="flex items-start gap-2">
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold text-frost-900">{{ $item->name }}</p>

            {{-- An item without a best before date and place shows no meta
                 information at all. The category is only used for filtering
                 (see InventoryOverview), so it never shows up in a list. --}}
            @if ($item->best_before || $freezerName || $drawerName)
                <p class="mt-1 flex flex-wrap items-center gap-1.5 text-xs {{ $metaClass }}">
                    @if ($item->best_before)
                        {{-- A warning is always shown, "frisch" only where the
                             row has room for it (see "mhdWarningsOnly"). --}}
                        @if (! $mhdWarningsOnly || $item->isExpired() || $item->isExpiringSoon())
                            <span class="badge {{ $item->stateBadgeClass() }}">{{ $item->stateLabel() }}</span>
                        @endif

                        {{-- Without the state the date stands alone, so the dot
                             between the two would hang in the air. --}}
                        @unless ($mhdWarningsOnly)
                            <span>{{ $item->bestBeforeLabel() }}</span>
                            <span class="text-frost-400">·</span>
                        @endunless

                        <span>{{ $item->formattedBestBefore() }}</span>
                    @endif
                    @if ($freezerName)
                        {{-- Names can get long, so the badge truncates instead of pushing the row apart. --}}
                        <span class="badge badge-neutral max-w-full">
                            <x-icon name="freezer" class="h-3.5 w-3.5 shrink-0" />
                            <span class="min-w-0 truncate">{{ $freezerName }}</span>
                        </span>
                    @endif
                    @if ($drawerName && $item->drawer)
                        {{-- Where the item lies, as the way into it: the badge
                             is small, so the chevron and the label of the link
                             make its purpose clear on a phone. --}}
                        <a href="{{ route('drawers.show', $item->drawer) }}?item={{ $item->getKey() }}"
                           wire:navigate class="badge badge-neutral max-w-full transition hover:bg-frost-300"
                           aria-label="{{ __('items.open_in_drawer_aria', ['drawer' => $drawerName]) }}">
                            <span class="min-w-0 truncate">{{ $drawerName }}</span>
                            <x-icon name="chevron-right" class="h-3.5 w-3.5 shrink-0" />
                        </a>
                    @endif
                </p>
            @endif
        </div>

        <div class="flex shrink-0 items-center gap-1">
            <button type="button"
                    wire:click="$dispatch('confirm-dialog', { name: 'item-delete-{{ $item->id }}' })"
                    class="btn btn-ghost btn-icon text-danger-600 dark:text-danger-400" aria-label="{{ __('items.delete_aria') }}">
                <x-icon name="trash" class="h-4 w-4" />
            </button>

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
        </div>
    </div>

    @if ($editable)
        <div class="mt-2 flex items-center justify-end gap-1 border-t border-frost-100 pt-2">
            <button type="button" wire:click="startEditingItem({{ $item->id }})"
                    class="btn btn-ghost btn-icon" aria-label="{{ __('items.edit_aria') }}">
                <x-icon name="pencil" class="h-4 w-4" />
            </button>

            <button type="button" wire:click="startMovingItem({{ $item->id }})"
                    class="btn btn-ghost btn-icon" aria-label="{{ __('items.move_aria') }}">
                <x-icon name="move" class="h-4 w-4" />
            </button>
        </div>
    @endif

    {{-- The question of this row. Every row carries its own dialog; the name
         keeps them apart (see components/confirm-dialog.blade.php). --}}
    <x-confirm-dialog
        :name="'item-delete-'.$item->id"
        :title="__('items.delete_confirm_title')"
        :message="__('items.delete_confirm_message', ['name' => $item->name])"
        :action="'deleteItem('.$item->id.')'" />
</div>
