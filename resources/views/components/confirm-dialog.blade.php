@props([
    'name',
    'title',
    'action',
    'message' => null,
    'confirmLabel' => 'Löschen',
    'icon' => 'trash',
    'variant' => 'danger',
])

{{--
 | The confirmation that belongs to the app instead of to the browser. Browser
 | dialogs cannot be styled and look foreign on a phone, so every destructive
 | action opens this sheet instead.
 |
 | The button that starts the action only dispatches the event below; the action
 | itself (wire:click) sits on the button inside the dialog, so the answer of the
 | user and the request travel together. Closing happens right away, before the
 | request is sent.
 |
 |     <button type="button"
 |             wire:click="$dispatch('confirm-dialog', { name: 'drawer-delete' })">
 |         Schublade löschen
 |     </button>
 |
 |     <x-confirm-dialog name="drawer-delete" title="Schublade löschen?"
 |                       :message="'„'.$drawer->name.'“ wird mit allen Artikeln entfernt.'"
 |                       :action="'deleteDrawer('.$drawer->id.')'" />
 |
 | Inside a loop every row gets its own dialog; the name keeps them apart.
--}}
<div x-data="{ open: false }"
     x-cloak
     x-show="open"
     x-on:confirm-dialog.window="
         if ($event.detail.name === @js($name)) {
             open = true;
             $nextTick(() => $refs.confirm.focus());
         }
     "
     x-on:keydown.escape.window="open = false"
     class="fixed inset-0 z-50 flex items-end justify-center px-4 pb-6 pt-4 sm:items-center"
     role="dialog" aria-modal="true" aria-labelledby="confirm-{{ $name }}-title">

    <div class="absolute inset-0 bg-frost-950/50 backdrop-blur-sm" x-on:click="open = false" aria-hidden="true"></div>

    <div class="relative w-full max-w-sm rounded-[1.25rem] bg-surface p-5 shadow-2xl">
        <h3 id="confirm-{{ $name }}-title" class="text-base font-bold text-frost-900">{{ $title }}</h3>

        @if ($message)
            <p class="mt-2 text-sm text-frost-600">{{ $message }}</p>
        @endif

        <div class="mt-4 flex flex-col gap-2 sm:flex-row-reverse">
            <button type="button" x-ref="confirm" x-on:click="open = false" wire:click="{{ $action }}"
                    class="{{ $variant === 'danger' ? 'btn btn-danger' : 'btn btn-primary' }} sm:flex-1">
                <x-icon :name="$icon" class="h-5 w-5" />
                {{ $confirmLabel }}
            </button>

            <button type="button" x-on:click="open = false" class="btn btn-ghost sm:flex-1">
                Abbrechen
            </button>
        </div>
    </div>
</div>
