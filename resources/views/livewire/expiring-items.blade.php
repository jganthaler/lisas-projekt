<div class="space-y-4">
    <div>
        <h2 class="text-base font-bold text-frost-900">MHD-Warnungen</h2>
        <p class="text-xs text-frost-500">
            Alles, was abgelaufen ist oder in den nächsten {{ $warningDays }} Tagen abläuft – in allen deinen
            Gefrierschränken.
        </p>
    </div>

    <x-notice :message="$itemFeedback" />

    @if ($expired->isEmpty() && $expiringSoon->isEmpty())
        <div class="card px-4 py-12 text-center">
            <span class="mx-auto grid h-14 w-14 place-items-center rounded-control bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
                <x-icon name="check" class="h-7 w-7" />
            </span>
            <p class="mt-3 text-sm font-semibold text-frost-900">Alles frisch</p>
            <p class="mt-1 text-sm text-frost-500">Zurzeit muss nichts aufgebraucht werden.</p>
            <a href="{{ route('inventory.index') }}" wire:navigate class="btn btn-secondary mt-4">
                <x-icon name="list" class="h-4 w-4" />
                Zum Inventar
            </a>
        </div>
    @endif

    @if ($expired->isNotEmpty())
        <section class="space-y-2">
            <h3 class="flex items-center gap-2 px-1 text-sm font-semibold text-red-700 dark:text-red-400">
                <x-icon name="alert" class="h-4 w-4" />
                Abgelaufen
                <span class="badge badge-danger">{{ $expired->count() }}</span>
            </h3>

            @foreach ($expired as $item)
                <x-item-row :item="$item" :confirming="$confirmingItemId === $item->id"
                            :freezer-name="$item->drawer?->freezer?->name"
                            :drawer-name="$item->drawer?->name" />
            @endforeach
        </section>
    @endif

    @if ($expiringSoon->isNotEmpty())
        <section class="space-y-2">
            <h3 class="flex items-center gap-2 px-1 text-sm font-semibold text-amber-800 dark:text-amber-300">
                <x-icon name="alert" class="h-4 w-4" />
                Bald abgelaufen
                <span class="badge badge-warn">{{ $expiringSoon->count() }}</span>
            </h3>

            @foreach ($expiringSoon as $item)
                <x-item-row :item="$item" :confirming="$confirmingItemId === $item->id"
                            :freezer-name="$item->drawer?->freezer?->name"
                            :drawer-name="$item->drawer?->name" />
            @endforeach
        </section>
    @endif
</div>
