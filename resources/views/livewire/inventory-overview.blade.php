@php
    // With more than one freezer the list mixes them, so every row tells where
    // the item comes from.
    $showFreezerNames = $freezers->count() > 1 && $selectedFreezer === null;

    // A single freezer needs no filter - the panel says so instead of offering
    // a choice that could never change the list.
    $showFreezerFilter = $freezers->count() > 1;
@endphp

<div class="space-y-4">
    <div class="flex items-end justify-between gap-3">
        <div class="min-w-0">
            <h2 class="text-base font-bold text-frost-900">{{ __('inventory.title') }}</h2>
            <p class="truncate text-xs text-frost-500">
                {{ $selectedFreezer?->name ?? ($freezers->count() === 1 ? $freezers->first()?->name : __('inventory.all_freezers')) }}
            </p>
        </div>
        <span class="badge badge-neutral">{{ trans_choice('common.items', $totalCount) }}</span>
    </div>

    <x-notice :message="$itemFeedback" />

    <div class="card space-y-3 p-3">
        <div class="flex gap-2">
            <div class="relative min-w-0 flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-frost-400" />
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('inventory.search_placeholder') }}"
                       class="input pl-9" aria-label="{{ __('inventory.search_aria') }}">
            </div>

            {{-- The freezer filter hides behind this button. --}}
            <button type="button" wire:click="toggleFilters"
                    class="btn btn-secondary btn-icon relative shrink-0"
                    aria-label="{{ __('inventory.filter_aria') }}" aria-expanded="{{ $showFilters ? 'true' : 'false' }}">
                <x-icon name="filter" class="h-5 w-5" />

                @if ($hasFilterSelection)
                    <span class="absolute -right-1 -top-1 h-2.5 w-2.5 rounded-full bg-brand-500 ring-2 ring-surface"></span>
                @endif
            </button>
        </div>

        @if ($showFilters)
            <div class="space-y-3 border-t border-frost-100 pt-3">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="text-sm font-semibold text-frost-700">{{ __('inventory.filter') }}</h3>

                    @if ($hasFilterSelection)
                        <button type="button" wire:click="clearFilterSelection"
                                class="text-xs font-semibold text-brand-700 underline-offset-2 hover:underline dark:text-brand-400">
                            {{ __('inventory.reset_filters') }}
                        </button>
                    @endif
                </div>

                @if ($showFreezerFilter)
                    <div>
                        <p class="label">{{ __('nav.freezer') }}</p>
                        <div class="flex flex-wrap gap-1.5">
                            <button type="button" wire:click="filterByFreezer('')"
                                    class="badge rounded-control px-3 py-1.5 {{ $selectedFreezer === null ? 'badge-ok' : 'badge-neutral' }}">
                                {{ __('inventory.all_freezers') }}
                            </button>

                            @foreach ($freezers as $option)
                                <button type="button" wire:click="filterByFreezer('{{ $option->id }}')"
                                        class="badge rounded-control px-3 py-1.5 {{ $selectedFreezer?->is($option) ? 'badge-ok' : 'badge-neutral' }}">
                                    {{ $option->name }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @else
                    <p class="text-sm text-frost-500">{{ __('inventory.single_freezer') }}</p>
                @endif

                {{-- The category filter is always available, no matter how many
                     freezers the household has. --}}
                <div>
                    <p class="label">{{ __('common.category') }}</p>
                    <div class="flex flex-wrap gap-1.5">
                        <button type="button" wire:click="filterByCategory('')"
                                class="badge rounded-control px-3 py-1.5 {{ $selectedCategory === null ? 'badge-ok' : 'badge-neutral' }}">
                            {{ __('inventory.all_categories') }}
                        </button>

                        @foreach ($categories as $value => $label)
                            <button type="button" wire:click="filterByCategory('{{ $value }}')"
                                    class="badge rounded-control px-3 py-1.5 {{ $selectedCategory === $value ? 'badge-ok' : 'badge-neutral' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>

    @if ($items->isEmpty())
        <div class="card px-4 py-10 text-center">
            <x-icon name="snowflake" class="mx-auto h-8 w-8 text-frost-300" />
            <p class="mt-2 text-sm font-medium text-frost-700">
                {{ $isFiltered ? __('inventory.nothing_found') : __('inventory.empty') }}
            </p>

            {{-- A list that ends without a way out is a dead end: a search that
                 found nothing is taken back with one tap, and an inventory
                 without items leads to the freezer where the first one is
                 entered. --}}
            @if ($isFiltered)
                <button type="button" wire:click="clearFilters" class="btn btn-secondary mt-4">
                    <x-icon name="close" class="h-4 w-4" />
                    {{ __('inventory.show_all') }}
                </button>
            @else
                <a href="{{ route('freezer.closed') }}" wire:navigate class="btn btn-secondary mt-4">
                    <x-icon name="freezer" class="h-4 w-4" />
                    {{ __('inventory.to_freezer') }}
                </a>
            @endif
        </div>
    @else
        <div class="space-y-2">
            @foreach ($items as $item)
                {{-- The row stays quiet - it names the date and warns only while
                     it matters - and it is painted in the colour of its
                     category (see components/item-row). --}}
                <x-item-row :item="$item"
                            :drawer-name="$item->drawer?->name"
                            :freezer-name="$showFreezerNames ? $item->drawer?->freezer?->name : null"
                            :mhd-warnings-only="true"
                            :category-tint="true" />
            @endforeach
        </div>
    @endif
</div>
