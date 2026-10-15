<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0d8280">

    <title>{{ isset($title) ? __($title).' · '.config('app.name') : config('app.name') }}</title>

    <x-theme-script />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-frost-100 antialiased">
    {{--
     | Das Layout der Rechtsseiten. Es gleicht dem Rahmen der App (Kopfzeile mit
     | Wortmarke, Fußzeile mit den drei Links), zeigt aber keine Navigation und
     | keinen Avatar: Impressum, Datenschutz und Nutzungsbedingungen sind auch
     | ohne Konto lesbar.
     |
     | Anders als layouts/app.blade.php und layouts/auth.blade.php arbeitet diese
     | Datei mit @yield statt mit $slot, weil die Seiten von einem Controller
     | gerendert werden (App\Http\Controllers\LegalController) und nicht von
     | einer Livewire-Komponente.
    --}}
    @php
        // Wer angemeldet ist, kommt in die App zurück; Gäste zur Anmeldung.
        $signedIn = auth()->check();
        $backUrl = $signedIn ? route('freezer.closed') : route('login');
        $backLabel = $signedIn ? __('legal.shared.back_to_app') : __('legal.shared.to_login');
    @endphp

    <header class="sticky top-0 z-30 border-b border-frost-200 bg-surface/90 backdrop-blur">
        <div class="shell flex items-center gap-3 py-2.5">
            <a href="{{ $backUrl }}">
                <x-logo class="text-base" />
            </a>

            <div class="ml-auto flex items-center gap-1.5">
                <x-theme-toggle />

                <a href="{{ $backUrl }}" class="btn btn-ghost btn-icon"
                   aria-label="{{ $backLabel }}" title="{{ $backLabel }}">
                    <x-icon name="arrow-left" class="h-5 w-5" />
                </a>
            </div>
        </div>
    </header>

    <main class="shell py-4 pb-10">
        @yield('content')
    </main>

    <footer class="shell pb-12">
        <nav class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs"
             aria-label="{{ __('legal.shared.legal_heading') }}">
            @foreach (['legal.imprint' => 'imprint', 'legal.privacy' => 'privacy', 'legal.terms' => 'terms'] as $route => $document)
                <a href="{{ route($route) }}"
                   @class([
                       'font-medium text-brand-700 dark:text-brand-400' => request()->routeIs($route),
                       'text-frost-500 hover:text-brand-700 dark:hover:text-brand-400' => ! request()->routeIs($route),
                   ])>{{ __('legal.'.$document.'.title') }}</a>
            @endforeach
        </nav>
    </footer>
</body>
</html>
