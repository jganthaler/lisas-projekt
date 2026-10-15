<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0d8f90">

    <title>{{ isset($title) ? $title.' · '.config('app.name') : config('app.name') }}</title>

    <x-theme-script />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="relative flex min-h-screen flex-col bg-brand-900 antialiased dark:bg-brand-950">
    <div id="loading-bar" data-active="false" aria-hidden="true"><span></span></div>

    {{-- The auth pages have their own dark background, so the toggle stays light. --}}
    <x-theme-toggle class="absolute right-3 top-3 text-brand-100 hover:bg-brand-500/20" />

    <div class="flex flex-1 items-center justify-center px-4 py-10">
        <div class="w-full max-w-sm">
            <div class="mb-6 text-center">
                <h1>
                    <x-logo variant="on-dark" class="text-2xl" />
                </h1>
                <p class="mt-2 text-sm text-brand-100/80">Gemeinsam den Überblick behalten.</p>
            </div>

            <div class="card p-5">
                <x-flash-message />

                {{ $slot }}
            </div>
        </div>
    </div>

    @livewireScripts
</body>
</html>
