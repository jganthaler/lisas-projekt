@props(['label'])

{{--
 | The "⋮" menu of a row: the actions that belong to one entry of a list stay
 | behind this button, so the row itself keeps the room for what it shows - the
 | name of a drawer, the name of an item and its best before date.
 |
 | The actions come as the slot, one button each, with icon and label:
 |
 |     <x-menu :label="__('freezer.drawer_menu_aria', ['name' => $drawer->name])">
 |         <button type="button" wire:click="startRenaming(1)" class="menu-item" role="menuitem">
 |             <x-icon name="pencil" class="h-4 w-4" />
 |             {{ __('freezer.rename_drawer') }}
 |         </button>
 |     </x-menu>
 |
 | The trigger is the "⋮" button. A place that needs a button with a label of its
 | own brings it as the "trigger" slot and toggles the panel itself - the state
 | of the menu is "open" (the profile picture does this, see
 | resources/views/livewire/profile/profile-page.blade.php):
 |
 |     <x-menu :label="__('profile.edit_avatar')">
 |         <x-slot:trigger>
 |             <button type="button" x-on:click="open = ! open"
 |                     x-bind:aria-expanded="open ? 'true' : 'false'"
 |                     aria-haspopup="menu" class="btn btn-secondary w-full">
 |                 <x-icon name="camera" class="h-5 w-5" />
 |                 {{ __('profile.edit_avatar') }}
 |             </button>
 |         </x-slot:trigger>
 |
 |         <button type="button" ...>...</button>
 |     </x-menu>
 |
 | The panel is drawn by .menu-panel and .menu-item (see resources/css/app.css);
 | it floats over the rows below and over the bottom bar of the app frame. A
 | destructive action only opens the dialog of the app - the answer and the
 | request travel together inside that dialog (see components/confirm-dialog).
 |
 | The state lives in the markup (Alpine), so several rows can carry a menu at
 | the same time without knowing of each other. It closes with the tap next to
 | the panel, with Escape and with the tap on one of its actions - the action
 | itself then runs, because the panel only closes and does not swallow it.
 |
 | The label belongs to the row: "Weitere Aktionen für „Schublade 1“", so a
 | screen reader does not read a bare "⋮" next to every entry of a list. It
 | names the button and the panel of the menu.
--}}
<div class="relative shrink-0" x-data="{ open: false }"
     x-on:keydown.escape.window="open = false"
     x-on:click.outside="open = false">
    @isset($trigger)
        {{ $trigger }}
    @else
        <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open ? 'true' : 'false'"
                aria-haspopup="menu" class="btn btn-ghost btn-icon" aria-label="{{ $label }}">
            <x-icon name="more" class="h-5 w-5" />
        </button>
    @endisset

    <div x-cloak x-show="open" x-on:click="open = false" class="menu-panel" role="menu"
         aria-label="{{ $label }}">
        {{ $slot }}
    </div>
</div>
