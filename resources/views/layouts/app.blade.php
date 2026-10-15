<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0d8280">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <title>{{ isset($title) ? __($title).' · '.config('app.name') : config('app.name') }}</title>

    <x-theme-script />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-frost-100 antialiased">
    <div id="loading-bar" data-active="false" aria-hidden="true"><span></span></div>

    @php
        $currentUser = auth()->user();

        // Resolving the active freezer also provisions the very first one for a
        // brand new account - every screen of the app frame relies on that.
        if ($currentUser) {
            \App\Support\FreezerContext::current($currentUser);
        }

        // The MHD counter of the app frame - the round number on the MHD field of
        // the bottom bar - refers to all freezers the user may access, not only to
        // the one that is currently selected.
        $watchCount = $currentUser?->warningItemCount() ?? 0;
    @endphp

    <header class="sticky top-0 z-30 border-b border-frost-200 bg-surface/90 backdrop-blur">
        <div class="shell flex items-center gap-3 py-2.5">
            {{-- The top row carries the word mark of the app, the name of the
                 active freezer lives on the freezer page itself. --}}
            <a href="{{ route('freezer.closed') }}" wire:navigate>
                <x-logo class="text-base" />
            </a>

            <div class="ml-auto flex items-center gap-1.5">
                <x-theme-toggle />

                <a href="{{ route('sharing.index') }}" wire:navigate class="btn btn-ghost btn-icon"
                   aria-label="{{ __('nav.share') }}">
                    <x-icon name="share" class="h-5 w-5" />
                </a>

                <a href="{{ route('profile.edit') }}" wire:navigate aria-label="{{ __('nav.profile') }}">
                    <x-avatar :user="$currentUser" size="h-9 w-9" />
                </a>
            </div>
        </div>
    </header>

    <main class="shell pb-28 pt-4">
        <x-flash-message />

        {{ $slot }}
    </main>

    <nav class="safe-bottom fixed inset-x-0 bottom-0 z-30 border-t border-frost-200 bg-surface/95 backdrop-blur">
        <div class="shell flex gap-1.5 pt-2">
            <x-nav-link :href="route('freezer.closed')" :active="request()->routeIs('freezer.*')"
                        :label="__('nav.freezer')" icon="freezer" />
            <x-nav-link :href="route('inventory.index')" :active="request()->routeIs('inventory.*')"
                        :label="__('nav.inventory')" icon="list" />
            <x-nav-link :href="route('expiring.index')" :active="request()->routeIs('expiring.*')"
                        :label="__('nav.expiring')" icon="alert" :badge="$watchCount" />
        </div>
    </nav>

    @livewireScripts
</body>
</html>
