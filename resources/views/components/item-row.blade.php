@props(['item', 'editable' => false, 'drawerName' => null, 'freezerName' => null])

{{--
 | One item of the inventory. The buttons call the actions of the surrounding
 | Livewire component (see App\Livewire\Concerns\InteractsWithItems), so the row
 | can be reused by the drawer, the inventory overview and the MHD list.
 |
 | Deleting always asks through the dialog of the app (see
 | components/confirm-dialog), never through the dialog of the browser: the trash
 | button only opens it, and the answer travels with the button inside it.
--}}
<div class="card p-3" wire:key="item-{{ $item->id }}">
    <div class="flex items-start gap-2">
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold text-frost-900">{{ $item->name }}</p>

            {{-- An item without a best before date and place shows no meta
                 information at all. The category is only used for filtering
                 (see InventoryOverview), so it never shows up in a list. --}}
            @if ($item->best_before || $freezerName || $drawerName)
                <p class="mt-1 flex flex-wrap items-center gap-1.5 text-xs text-frost-500">
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
