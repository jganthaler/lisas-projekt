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
            <h2 class="text-base font-bold text-frost-900">Inventar</h2>
            <p class="truncate text-xs text-frost-500">
                {{ $selectedFreezer?->name ?? ($freezers->count() === 1 ? $freezers->first()?->name : 'Alle Gefrierschränke') }}
            </p>
        </div>
        <span class="badge badge-neutral">{{ $totalCount }} Artikel</span>
    </div>

    <x-notice :message="$itemFeedback" />

    <div class="card space-y-3 p-3">
        <div class="flex gap-2">
            <div class="relative min-w-0 flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-frost-400" />
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Artikel suchen …"
                       class="input pl-9" aria-label="Artikel suchen">
            </div>

            {{-- The freezer filter hides behind this button. --}}
            <button type="button" wire:click="toggleFilters"
                    class="btn btn-secondary btn-icon relative shrink-0"
                    aria-label="Filter" aria-expanded="{{ $showFilters ? 'true' : 'false' }}">
                <x-icon name="filter" class="h-5 w-5" />

                @if ($hasFilterSelection)
                    <span class="absolute -right-1 -top-1 h-2.5 w-2.5 rounded-full bg-brand-500 ring-2 ring-white"></span>
                @endif
            </button>
        </div>

        @if ($showFilters)
            <div class="space-y-3 border-t border-frost-100 pt-3">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="text-sm font-semibold text-frost-700">Filter</h3>

                    @if ($hasFilterSelection)
                        <button type="button" wire:click="clearFilterSelection"
                                class="text-xs font-semibold text-brand-700 underline-offset-2 hover:underline">
                            Filter zurücksetzen
                        </button>
                    @endif
                </div>

                @if ($showFreezerFilter)
                    <div>
                        <p class="label">Gefrierschrank</p>
                        <div class="flex flex-wrap gap-1.5">
                            <button type="button" wire:click="filterByFreezer('')"
                                    class="badge rounded-control px-3 py-1.5 {{ $selectedFreezer === null ? 'badge-ok' : 'badge-neutral' }}">
                                Alle Gefrierschränke
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
                    <p class="text-sm text-frost-500">Nur ein Gefrierschrank – hier gibt es nichts zu filtern.</p>
                @endif
            </div>
        @endif
    </div>

    @if ($items->isEmpty())
        <div class="card px-4 py-10 text-center">
            <x-icon name="snowflake" class="mx-auto h-8 w-8 text-frost-300" />
            <p class="mt-2 text-sm font-medium text-frost-700">
                {{ $isFiltered ? 'Dazu wurde nichts gefunden.' : 'Das Inventar ist noch leer.' }}
            </p>

            @if ($isFiltered)
                <button type="button" wire:click="clearFilters" class="btn btn-secondary mt-3">
                    <x-icon name="close" class="h-4 w-4" />
                    Filter zurücksetzen
                </button>
            @endif
        </div>
    @else
        <div class="space-y-2">
            @foreach ($items as $item)
                <x-item-row :item="$item"
                            :confirming="$confirmingItemId === $item->id"
                            :drawer-name="$item->drawer?->name"
                            :freezer-name="$showFreezerNames ? $item->drawer?->freezer?->name : null" />
            @endforeach
        </div>
    @endif
</div>
