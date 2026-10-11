<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0d8f90">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <title>{{ isset($title) ? $title.' · '.config('app.name') : config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-frost-100 antialiased">
    <div id="loading-bar" data-active="false" aria-hidden="true"><span></span></div>

    @php
        $currentUser = auth()->user();
        $currentFreezer = $currentUser ? \App\Support\FreezerContext::current($currentUser) : null;
        // Every MHD counter of the app frame - the warning pill in the header and
        // the round counter on the MHD field - refers to all freezers the user may
        // access, not only to the one that is currently selected.
        $watchCount = $currentUser?->warningItemCount() ?? 0;
    @endphp

    <header class="sticky top-0 z-30 border-b border-frost-200 bg-white/90 backdrop-blur">
        <div class="shell flex items-center gap-3 py-2.5">
            <a href="{{ route('freezer.closed') }}" wire:navigate class="flex min-w-0 items-center gap-2.5">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-control bg-brand-600 text-white">
                    <x-icon name="freezer" class="h-5 w-5" />
                </span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold text-frost-900">
                        {{ $currentFreezer?->name ?? config('app.name') }}
                    </span>
                    <span class="block truncate text-xs text-frost-500">
                        {{ $currentUser?->displayName() }}
                    </span>
                </span>
            </a>

            <div class="ml-auto flex items-center gap-1.5">
                @if ($watchCount > 0)
                    <a href="{{ route('expiring.index') }}" wire:navigate
                       class="badge badge-warn" aria-label="{{ $watchCount }} Artikel prüfen">
                        <x-icon name="alert" class="h-4 w-4" />
                        {{ $watchCount }}
                    </a>
                @endif

                <a href="{{ route('sharing.index') }}" wire:navigate class="btn btn-ghost btn-icon"
                   aria-label="Teilen">
                    <x-icon name="share" class="h-5 w-5" />
                </a>

                <a href="{{ route('profile.edit') }}" wire:navigate aria-label="Profil">
                    <x-avatar :user="$currentUser" size="h-9 w-9" />
                </a>
            </div>
        </div>
    </header>

    <main class="shell pb-28 pt-4">
        <x-flash-message />

        {{ $slot }}
    </main>

    <nav class="safe-bottom fixed inset-x-0 bottom-0 z-30 border-t border-frost-200 bg-white/95 backdrop-blur">
        <div class="shell flex gap-1.5 pt-2">
            <x-nav-link :href="route('freezer.closed')" :active="request()->routeIs('freezer.*')"
                        label="Gefrierschrank" icon="freezer" />
            <x-nav-link :href="route('inventory.index')" :active="request()->routeIs('inventory.*')"
                        label="Inventar" icon="list" />
            <x-nav-link :href="route('expiring.index')" :active="request()->routeIs('expiring.*')"
                        label="MHD" icon="alert" :badge="$watchCount" />
        </div>
    </nav>

    @livewireScripts
</body>
</html>
