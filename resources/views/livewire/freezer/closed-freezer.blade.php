<div class="space-y-4">
    {{-- The freezers of the household: the header of the field carries its title
         and the two actions of the field - the plus for another freezer and the
         "⋮" of the active one -, below a hairline stands the active freezer with
         the list of all of them. --}}
    <section class="card">
        <div class="flex items-center justify-between gap-2 px-4 pb-2 pt-3">
            <h3 class="text-sm font-semibold text-frost-700">{{ __('freezer.mine') }}</h3>

            {{-- The plus and the menu are the same ghost button, so both stand on
                 one line on a phone as well and stay easy to hit. The plus is the
                 only way to another freezer - it left the menu - and the menu
                 keeps what belongs to the active freezer: renaming and deleting
                 it. A member has nothing to do here and sees no menu. --}}
            <div class="flex items-center gap-1">
                <button type="button" wire:click="startAddingFreezer" class="btn btn-ghost btn-icon"
                        aria-label="{{ __('freezer.add') }}">
                    <x-icon name="plus" class="h-5 w-5" />
                </button>

                @if ($freezer->isOwnedBy(auth()->user()))
                    <x-menu :label="__('freezer.menu_aria', ['name' => $freezer->name])">
                        <button type="button" wire:click="startRenaming({{ $freezer->id }})"
                                class="menu-item" role="menuitem">
                            <x-icon name="pencil" class="h-4 w-4" />
                            {{ __('freezer.edit') }}
                        </button>

                        <button type="button"
                                wire:click="$dispatch('confirm-dialog', { name: 'freezer-delete-{{ $freezer->id }}' })"
                                class="menu-item menu-item-danger" role="menuitem">
                            <x-icon name="trash" class="h-4 w-4" />
                            {{ __('freezer.delete') }}
                        </button>
                    </x-menu>
                @endif
            </div>
        </div>

        {{-- The dialog of the delete sits beside the menu, never inside it: the
             panel of the menu is hidden and would hide the dialog with it. --}}
        @if ($freezer->isOwnedBy(auth()->user()))
            <x-confirm-dialog
                :name="'freezer-delete-'.$freezer->id"
                :title="__('freezer.delete_confirm_title')"
                :message="__('freezer.delete_confirm_message', ['name' => $freezer->name])"
                :action="'deleteFreezer('.$freezer->id.')'"
                :confirm-label="__('common.delete_forever')" />
        @endif

        {{-- The hairline under the header runs over the full width of the field
             and keeps the header apart from the freezer it acts on. --}}
        <span class="block border-t border-frost-100"></span>

        {{-- The area under the hairline carries the very tone of the block of
             the active freezer below it (bg-brand-50, in the dark scheme
             bg-brand-950): the field reads as one band instead of a block on a
             white card. Both places name the same class of the palette of the
             app, never a value of their own - the block keeps its shape
             ("rounded-2xl") and everything inside this area - icon, name, arrow,
             list, distances - stays as it was.

             The band reaches over the full width of the field: the card brings
             no padding of its own and this area brings its own ("p-3"), as the
             header does. As long as no form stands under it, it is the last
             piece of the card and takes its bottom corners ("rounded-b-card",
             the "--radius-card" of resources/css/app.css), so the tone stays
             inside them; with a form open the hairline of that form closes the
             band and the corners stay square. --}}
        <div @class([
            'bg-brand-50 overflow-hidden dark:bg-brand-950',
            'rounded-b-card' => ! $addingFreezer && ! $editingFreezerId,
        ]) x-data="{ open: false }">
            {{-- The active freezer: the tile with the icon of the app, beside it its
                 name in the largest weight of the page and, on the right, the arrow
                 that opens the list of all freezers - a tap is what a phone has, and
                 the list stands in the flow instead of floating over the drawers, so
                 nothing covers what follows. Whether it is the own or a shared
                 freezer is not written here; the list marks the active one.

                 The tile is exactly as large as the tile of a drawer below it
                 ("h-10 w-10", the glyph "h-5 w-5"), so the two icons of the page
                 stand symmetrically one below the other - only the filled tone of
                 the brand marks the active freezer here, the tile of a drawer
                 stays light. --}}
            <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open ? 'true' : 'false'"
                    aria-haspopup="true"
                    class="flex w-full items-center rounded-none gap-3 bg-brand-50 px-4 py-3.5 text-left transition hover:bg-brand-100 dark:bg-brand-950 dark:hover:bg-brand-900">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-control bg-brand-600 text-white">
                    <x-icon name="freezer" class="h-6 w-6" />
                </span>

                <span class="min-w-0 flex-1 truncate text-base font-bold text-frost-900">{{ $freezer->name }}</span>

                <x-icon name="chevron-down"
                        class="h-5 w-5 shrink-0 text-brand-700 transition-transform dark:text-brand-300"
                        x-bind:class="{ 'rotate-180': open }" />
            </button>

            {{-- Every freezer, with the mark of the active one: the answer to the
                 tap on the arrow above. The list stays in the order of the names,
                 and a member may switch between the freezers as well. --}}
            <div x-cloak x-show="open" class="mt-2 space-y-1">
                @foreach ($freezers as $option)
                    <button type="button" wire:click="selectFreezer({{ $option->id }})"
                            wire:key="freezer-{{ $option->id }}"
                            aria-current="{{ $option->is($freezer) ? 'true' : 'false' }}"
                            class="flex w-full items-center gap-3 rounded-control px-4 py-2.5 text-left transition hover:bg-frost-50">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-control {{ $option->is($freezer) ? 'bg-brand-600 text-white' : 'bg-frost-100 text-frost-500' }}">
                            <x-icon name="freezer" class="h-5 w-5" />
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-frost-900">{{ $option->name }}</span>
                            @if ($option->is($freezer))
                                <span class="block truncate text-xs text-frost-500">{{ __('freezer.active') }}</span>
                            @endif
                        </span>

                        @if ($option->is($freezer))
                            <x-icon name="check" class="h-5 w-5 shrink-0 text-brand-600" />
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

        @if ($addingFreezer)
            {{-- Another freezer, e.g. the chest freezer in the cellar --}}
            <form wire:submit="addFreezer" class="space-y-3 border-t border-frost-100 p-3" wire:key="new-freezer">
                <div>
                    <label class="label" for="newFreezerName">{{ __('freezer.new_label') }}</label>
                    <input id="newFreezerName" type="text" wire:model="newFreezerName"
                           wire:keydown.escape="cancelAddingFreezer" placeholder="{{ __('freezer.new_placeholder') }}"
                           class="input @error('newFreezerName') input-error @enderror">
                    @error('newFreezerName')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- „Abbrechen“ links, die Aktion rechts - das Paar, mit dem
                     jedes Formular der App endet, in zwei gleich breiten
                     Spalten: so hängt die Breite eines Knopfes nicht an der
                     Länge seiner Beschriftung. --}}
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" wire:click="cancelAddingFreezer" class="btn btn-ghost w-full">
                        {{ __('common.cancel') }}
                    </button>

                    <button type="submit" class="btn btn-primary w-full">
                        <x-icon name="check" class="h-5 w-5" />
                        {{ __('common.create') }}
                    </button>
                </div>
            </form>
        @endif

        @if ($editingFreezerId)
            {{-- Renaming the active freezer: only its name changes, its drawers and
                 items stay - and the field above shows the new name at once. --}}
            <form wire:submit="renameFreezer" class="space-y-3 border-t border-frost-100 p-3"
                  wire:key="rename-freezer-{{ $editingFreezerId }}">
                <div>
                    <label class="label" for="editingFreezerName">{{ __('freezer.rename_label') }}</label>
                    <input id="editingFreezerName" type="text" wire:model="editingFreezerName"
                           wire:keydown.escape="cancelRenaming"
                           class="input @error('editingFreezerName') input-error @enderror">
                    @error('editingFreezerName')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Dasselbe Paar wie im Formular oben: „Abbrechen“ links,
                     „Speichern“ rechts, beide gleich groß. --}}
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" wire:click="cancelRenaming" class="btn btn-ghost w-full">
                        {{ __('common.cancel') }}
                    </button>

                    <button type="submit" class="btn btn-primary w-full">
                        <x-icon name="check" class="h-5 w-5" />
                        {{ __('common.save') }}
                    </button>
                </div>
            </form>
        @endif

    </section>

    @if ($drawers->isNotEmpty())
        <section class="space-y-2">
            <h3 class="px-1 text-sm font-semibold text-frost-700">{{ __('freezer.drawers_heading') }}</h3>

            @foreach ($drawers as $drawer)
                @php
                    $stats = $summary[$drawer->id] ?? ['items' => 0, 'warnings' => 0];
                @endphp

                {{-- A look into the drawer: its head sits on the light grey of
                     the field - the icon on the left, beside it the bold name,
                     the pill of the items that need attention and the chevron
                     into the drawer - and the hairline below it is the bottom
                     edge of that grey band: what follows lies on the white of
                     the card, alphabetically one below the other, the names and
                     nothing else. The amount stands in the drawer itself, so the
                     whole card stays the way in - there is nothing to edit here.

                     The band reaches over the whole width of the card: the card
                     itself carries no padding any more ("gap-0 p-0"), the band
                     and the list bring their own, and together they keep the
                     distances the card had before - 0.875rem above and below it
                     ("pt-3.5", "pb-3.5") and 0.75rem around the hairline
                     ("pb-3" above it, "pt-3" below it). Its top corners follow
                     the corners of the card ("rounded-t-card", the very
                     "--radius-card" of resources/css/app.css), so the grey stays
                     inside it.

                     The names below the hairline begin on the line of the name
                     above them: the head puts its icon (2.5rem) beside the name
                     with the gap of the head (0.75rem) between the two, and the
                     list carries exactly that distance on its left (see the
                     list below). The icon, the name and the hairline keep their
                     place, and every drawer indents the same way. --}}
                <a href="{{ route('drawers.show', $drawer) }}" wire:navigate
                   class="drawer-row flex-col items-stretch gap-0 p-0" wire:key="drawer-{{ $drawer->id }}">
                    {{-- The grey band: it carries the icon and the name of the
                         drawer and ends where the hairline closes it. --}}
                    <span class="block rounded-t-card bg-frost-200 px-4 pb-3 pt-3.5">
                        <span class="flex items-center gap-3">
                            {{-- The icon of the drawer on the tint of the app:
                                 brand-50 lifts it off the grey, and the glyph
                                 keeps the brand colour that carries the app -
                                 brand-600 on the light, brand-400 on the dark
                                 band (see the hero of the watch list). --}}
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-control bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
                                <x-icon name="list" class="h-5 w-5" />
                            </span>

                            <span class="min-w-0 flex-1 truncate text-sm font-bold text-frost-900">{{ $drawer->name }}</span>

                            @if ($stats['warnings'] > 0)
                                <span class="badge badge-warn">{{ $stats['warnings'] }}</span>
                            @endif

                            <x-icon name="chevron-right" class="h-5 w-5 shrink-0 text-frost-400" />
                        </span>
                    </span>

                    {{-- The hairline the cards of the app draw between a head and
                         what follows it (see the filter of the inventory). It is
                         the bottom edge of the grey band above it, and the list
                         below keeps the white of the card. --}}
                    <span class="block border-t border-frost-100"></span>

                    {{-- The white of the card carries the padding the card had
                         before (1rem at the sides), so the names below the
                         hairline keep the line they had. --}}
                    <div class="px-4 pb-3.5 pt-3">
                        @if ($drawer->items->isEmpty())
                            <span class="block text-xs text-frost-500">{{ __('drawers.empty') }}</span>
                        @else
                            {{-- The items arrive sorted (see Drawer::items()), so the
                                 list needs no order of its own. The amount of an item
                                 is not named here: the start page is read, not
                                 counted (the number stands in the drawer itself).

                                 The indent on the left is the icon of the head
                                 (h-10 w-10, 2.5rem) plus the gap of the head
                                 (gap-3, 0.75rem): "pl-13" is thirteen steps of the
                                 spacing scale (13 x 0.25rem) and so the very 3.25rem
                                 at which the name of the drawer above begins - the
                                 1rem of padding above it comes on top, the same
                                 1rem the grey band carries ("px-4"). Both numbers
                                 are fixed, so the line holds on a phone as well as
                                 on a wide screen. Only the names move: the hairline
                                 above them and the hint of an empty drawer
                                 ("drawers.empty") keep the full width. --}}
                            <ul class="space-y-2 pl-13">
                                @foreach ($drawer->items as $item)
                                    <li class="truncate text-xs text-frost-700">{{ $item->name }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
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
