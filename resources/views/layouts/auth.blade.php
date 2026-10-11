<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0d8f90">

    <title>{{ isset($title) ? $title.' · '.config('app.name') : config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex min-h-screen flex-col bg-brand-900 antialiased">
    <div id="loading-bar" data-active="false" aria-hidden="true"><span></span></div>

    <div class="flex flex-1 items-center justify-center px-4 py-10">
        <div class="w-full max-w-sm">
            <div class="mb-6 text-center">
                <span class="mx-auto grid h-14 w-14 place-items-center rounded-control bg-brand-500/20 text-brand-100">
                    <x-icon name="freezer" class="h-8 w-8" />
                </span>
                <h1 class="mt-3 text-2xl font-bold tracking-tight text-white">{{ config('app.name') }}</h1>
                <p class="mt-1 text-sm text-brand-100/80">Gemeinsam den Überblick behalten.</p>
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
