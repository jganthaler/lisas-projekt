<div class="space-y-4">
    <div class="flex items-center gap-3">
        <a href="{{ route('freezer.open') }}" wire:navigate class="btn btn-ghost btn-icon"
           aria-label="{{ __('drawers.back_aria') }}">
            <x-icon name="arrow-left" class="h-5 w-5" />
        </a>

        <div class="min-w-0 flex-1">
            <h2 class="truncate text-base font-bold text-frost-900">{{ $drawer->name }}</h2>
            <p class="truncate text-xs text-frost-500">
                {{ $freezer->name }} · {{ trans_choice('common.items', $items->count()) }}
            </p>
        </div>
    </div>

    <x-notice :message="$itemFeedback" />

    {{-- New item. The name field takes the cursor back after every item (the
         component fires "item-added"), so a whole drawer can be filled one line
         after the other: type, Enter, type, Enter. --}}
    <form wire:submit="addItem" class="card space-y-3 p-4"
          x-on:item-added.window="$refs.itemName.focus()">
        <h3 class="text-sm font-semibold text-frost-900">{{ __('drawers.add_heading') }}</h3>

        <div>
            <label class="label" for="name">{{ __('drawers.name_label') }}</label>
            <input id="name" type="text" wire:model="name" x-ref="itemName"
                   placeholder="{{ __('drawers.name_placeholder') }}"
                   class="input @error('name') input-error @enderror">
            @error('name')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex gap-3">
            <div class="w-28">
                <label class="label" for="quantity">{{ __('common.quantity') }}</label>
                <input id="quantity" type="number" min="1" max="999" inputmode="numeric" wire:model="quantity"
                       class="input @error('quantity') input-error @enderror">
                @error('quantity')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex-1">
                <label class="label" for="bestBefore">{{ __('drawers.best_before_optional') }}</label>
                <input id="bestBefore" type="date" wire:model="bestBefore"
                       class="input @error('bestBefore') input-error @enderror">
                @error('bestBefore')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Nobody knows the exact day a bag of peas goes bad, but most things
             keep for three or six months, a year or two. A tap fills the field
             above with today plus that span - saved is what the field holds,
             so the date can still be corrected by hand. --}}
        <div role="group" aria-label="{{ __('drawers.quick_best_before.label') }}">
            <p class="text-xs font-medium text-frost-500">{{ __('drawers.quick_best_before.label') }}</p>

            <div class="mt-1.5 flex flex-wrap gap-1.5">
                @foreach ($bestBeforePresets as $preset => $label)
                    <button type="button" wire:click="useBestBeforePreset('{{ $preset }}')" class="chip">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Sorting an item into a category is optional, but only then can the
             inventory be filtered by it later on. --}}
        <div>
            <label class="label" for="category">{{ __('drawers.category_optional') }}</label>
            <select id="category" wire:model="category" class="input @error('category') input-error @enderror">
                <option value="">{{ __('drawers.no_category') }}</option>
                @foreach ($categories as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('category')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary w-full" wire:loading.attr="disabled" wire:target="addItem">
            <x-icon name="plus" class="h-5 w-5" />
            {{ __('drawers.add') }}
        </button>
    </form>

    <section class="space-y-2">
        <h3 class="px-1 text-sm font-semibold text-frost-700">{{ __('drawers.contents') }}</h3>

        @forelse ($items as $item)
            @if ($editingItemId === $item->id)
                {{-- A form that replaces a row is scrolled into view: the row may
                     have been far down the list (or the page arrived from the
                     inventory with "?item=<id>"), and a form nobody sees looks
                     like nothing happened. "nearest" leaves a form alone that is
                     already on screen. --}}
                <form wire:submit="saveItem" class="card space-y-3 p-4" wire:key="edit-{{ $item->id }}"
                      x-init="$el.scrollIntoView({ block: 'nearest' })">
                    <div>
                        <label class="label" for="editName">{{ __('drawers.item_label') }}</label>
                        <input id="editName" type="text" wire:model="editName"
                               class="input @error('editName') input-error @enderror">
                        @error('editName')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex gap-3">
                        <div class="w-28">
                            <label class="label" for="editQuantity">{{ __('common.quantity') }}</label>
                            <input id="editQuantity" type="number" min="1" max="999" inputmode="numeric"
                                   wire:model="editQuantity" class="input @error('editQuantity') input-error @enderror">
                            @error('editQuantity')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex-1">
                            <label class="label" for="editBestBefore">{{ __('common.best_before') }}</label>
                            <input id="editBestBefore" type="date" wire:model="editBestBefore"
                                   class="input @error('editBestBefore') input-error @enderror">
                            @error('editBestBefore')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- The same four quick choices as in the form above, in the
                         same shape: the date of an item that is already there is
                         corrected just as often from a span ("another six
                         months") as it is entered the first time. --}}
                    <div role="group" aria-label="{{ __('drawers.quick_best_before.label') }}">
                        <p class="text-xs font-medium text-frost-500">{{ __('drawers.quick_best_before.label') }}</p>

                        <div class="mt-1.5 flex flex-wrap gap-1.5">
                            @foreach ($bestBeforePresets as $preset => $label)
                                <button type="button" wire:click="useBestBeforePreset('{{ $preset }}', 'editBestBefore')"
                                        class="chip">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="label" for="editCategory">{{ __('common.category') }}</label>
                        <select id="editCategory" wire:model="editCategory"
                                class="input @error('editCategory') input-error @enderror">
                            <option value="">{{ __('drawers.no_category') }}</option>
                            @foreach ($categories as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('editCategory')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-primary flex-1">
                            <x-icon name="check" class="h-5 w-5" />
                            {{ __('common.save') }}
                        </button>
                        <button type="button" wire:click="cancelEditingItem" class="btn btn-ghost">{{ __('common.cancel') }}</button>
                    </div>
                </form>
            @elseif ($movingItemId === $item->id)
                {{-- Moving instead of entering again: the item keeps its name,
                     its quantity, its best before date and its category and only
                     changes its place. The choice lists every drawer of the
                     household - also those of the other freezers (see
                     DrawerPage::moveTargets()). --}}
                <form wire:submit="moveItem" class="card space-y-3 p-4" wire:key="move-{{ $item->id }}"
                      x-init="$el.scrollIntoView({ block: 'nearest' })">
                    <div>
                        <h3 class="text-sm font-semibold text-frost-900">{{ __('drawers.move_heading') }}</h3>
                        <p class="mt-1 text-xs text-frost-500">{{ $item->name }}</p>
                    </div>

                    @if ($moveTargets->isEmpty())
                        {{-- A household with a single drawer has no target at all:
                             the form says so instead of offering a choice that
                             does not exist. --}}
                        <p class="text-sm text-frost-500">{{ __('drawers.move_none') }}</p>
                    @else
                        @php
                            // Two freezers may both have a "Schublade 1", so with
                            // more than one freezer the drawers are grouped by it.
                            $groupedByFreezer = $moveTargets->count() > 1;
                        @endphp

                        <div>
                            <label class="label" for="moveTargetDrawerId">{{ __('drawers.move_target_label') }}</label>
                            <select id="moveTargetDrawerId" wire:model="moveTargetDrawerId"
                                    class="input @error('moveTargetDrawerId') input-error @enderror">
                                <option value="">{{ __('drawers.move_target_placeholder') }}</option>

                                @foreach ($moveTargets as $targetFreezer)
                                    @if ($groupedByFreezer)
                                        <optgroup label="{{ $targetFreezer->name }}">
                                    @endif

                                    @foreach ($targetFreezer->drawers as $targetDrawer)
                                        <option value="{{ $targetDrawer->id }}">{{ $targetDrawer->name }}</option>
                                    @endforeach

                                    @if ($groupedByFreezer)
                                        </optgroup>
                                    @endif
                                @endforeach
                            </select>

                            @error('moveTargetDrawerId')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <div class="flex gap-2">
                        @if ($moveTargets->isNotEmpty())
                            <button type="submit" class="btn btn-primary flex-1">
                                <x-icon name="move" class="h-5 w-5" />
                                {{ __('drawers.move_submit') }}
                            </button>
                        @endif

                        <button type="button" wire:click="cancelMovingItem" class="btn btn-ghost flex-1">
                            {{ __('common.cancel') }}
                        </button>
                    </div>
                </form>
            @else
                <x-item-row :item="$item" :editable="true" />
            @endif
        @empty
            <div class="card px-4 py-10 text-center">
                <x-icon name="snowflake" class="mx-auto h-8 w-8 text-frost-300" />
                <p class="mt-2 text-sm font-medium text-frost-700">{{ __('drawers.empty') }}</p>
            </div>
        @endforelse
    </section>
</div>
