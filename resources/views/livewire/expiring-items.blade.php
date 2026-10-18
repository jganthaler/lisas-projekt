<div class="space-y-4">
    <div>
        <h2 class="text-base font-bold text-frost-900">{{ __('expiring.title') }}</h2>
        <p class="text-xs text-frost-500">
            {{ __('expiring.subtitle', ['days' => $warningDays]) }}
        </p>
    </div>

    <x-notice :message="$itemFeedback" />

    @if ($expired->isEmpty() && $expiringSoon->isEmpty())
        <div class="card px-4 py-12 text-center">
            <span class="mx-auto grid h-14 w-14 place-items-center rounded-control bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
                <x-icon name="check" class="h-7 w-7" />
            </span>
            <p class="mt-3 text-sm font-semibold text-frost-900">{{ __('expiring.all_fresh') }}</p>
            <p class="mt-1 text-sm text-frost-500">{{ __('expiring.nothing_to_use') }}</p>
            <a href="{{ route('inventory.index') }}" wire:navigate class="btn btn-secondary mt-4">
                <x-icon name="list" class="h-4 w-4" />
                {{ __('expiring.to_inventory') }}
            </a>
        </div>
    @endif

    @if ($expired->isNotEmpty())
        <section class="space-y-2">
            <h3 class="flex items-center gap-2 px-1 text-sm font-semibold text-danger-700 dark:text-danger-400">
                <x-icon name="alert" class="h-4 w-4" />
                {{ __('expiring.expired') }}
                <span class="badge badge-danger">{{ $expired->count() }}</span>
            </h3>

            @foreach ($expired as $item)
                <x-item-row :item="$item"
                            :freezer-name="$item->drawer?->freezer?->name"
                            :drawer-name="$item->drawer?->name" />
            @endforeach
        </section>
    @endif

    @if ($expiringSoon->isNotEmpty())
        <section class="space-y-2">
            <h3 class="flex items-center gap-2 px-1 text-sm font-semibold text-warning-800 dark:text-warning-300">
                <x-icon name="alert" class="h-4 w-4" />
                {{ __('expiring.soon') }}
                <span class="badge badge-warn">{{ $expiringSoon->count() }}</span>
            </h3>

            @foreach ($expiringSoon as $item)
                <x-item-row :item="$item"
                            :freezer-name="$item->drawer?->freezer?->name"
                            :drawer-name="$item->drawer?->name" />
            @endforeach
        </section>
    @endif
</div>
