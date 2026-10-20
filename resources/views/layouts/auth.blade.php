<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0d8280">

    {{-- Hier ist niemand angemeldet: die Kopie des Inventars und die wartenden
         Offline-Änderungen, die die Offline-Seite auf dem Gerät hält
         (resources/js/app.js), gehören damit gelöscht. --}}
    <meta name="offline-clear" content="1">

    <x-app-icons />

    <title>{{ isset($title) ? __($title).' · '.config('app.name') : config('app.name') }}</title>

    <x-theme-script />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
{{-- Die Anmeldeseiten liegen auf demselben hellen Grau wie die App selbst
     (bg-frost-100). Nur auf einem hellen Grund behält die Wortmarke ihre
     eigenen Farben - die Datei ist dafür gezeichnet; im Nachtmodus dreht die
     Palette das Grau um, dann wird die Marke invertiert
     (siehe components/logo.blade.php). --}}
<body class="relative flex min-h-screen flex-col bg-frost-100 antialiased">
    <div id="loading-bar" data-active="false" aria-hidden="true"><span></span></div>

    {{-- Der Umschalter sitzt wie auf den Rechtsseiten oben rechts; seine Farben
         kommen aus btn-ghost und passen zu beiden Schemata. --}}
    <x-theme-toggle class="absolute right-3 top-3" />

    <div class="flex flex-1 items-center justify-center px-4 py-10">
        <div class="w-full max-w-sm">
            <div class="mb-6 text-center">
                <h1>
                    <x-logo class="text-2xl" />
                </h1>
                <p class="mt-2 text-sm text-frost-500">{{ __('auth.tagline') }}</p>
            </div>

            <div class="card p-5">
                <x-flash-message />

                {{ $slot }}
            </div>

            {{-- Die Rechtsseiten sind auch ohne Konto erreichbar - der Verweis
                 gehört deshalb schon hierher, nicht erst ins Profil. --}}
            <nav class="mt-6 flex flex-wrap items-center justify-center gap-x-4 gap-y-1.5 text-xs"
                 aria-label="{{ __('legal.shared.legal_heading') }}">
                @foreach (['legal.imprint' => 'imprint', 'legal.privacy' => 'privacy', 'legal.terms' => 'terms'] as $route => $document)
                    <a href="{{ route($route) }}" class="text-frost-500 hover:text-brand-700 dark:hover:text-brand-400">
                        {{ __('legal.'.$document.'.title') }}
                    </a>
                @endforeach
            </nav>
        </div>
    </div>

    @livewireScripts
</body>
</html>
