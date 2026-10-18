@extends('layouts.legal', ['title' => 'legal.imprint.title'])

@php
    // Die Kontaktangabe, die in der Konfiguration noch fehlt - solange der
    // Hinweis erscheint, fehlt dem Impressum die Kontaktmöglichkeit (siehe
    // App\Support\Legal).
    $missing = \App\Support\Legal::missingFields();
@endphp

@section('content')
    <article class="card space-y-5 p-5">
        <header>
            <h1 class="text-lg font-bold text-frost-900">{{ __('legal.imprint.heading') }}</h1>
            <p class="mt-1 text-sm text-frost-500">{{ \App\Support\Legal::text('imprint.intro') }}</p>
        </header>

        @if ($missing)
            <div class="rounded-2xl bg-warning-50 px-4 py-3 text-sm leading-relaxed text-warning-800 dark:bg-warning-950 dark:text-warning-200">
                <p class="flex items-start gap-2 font-semibold">
                    <x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" />
                    {{ __('legal.imprint.incomplete_heading') }}
                </p>
                <p class="mt-1.5">{{ __('legal.imprint.incomplete_body') }}</p>
                <p class="mt-1.5 font-medium">{{ __('legal.imprint.missing_heading') }} {{ implode(', ', $missing) }}</p>
            </div>
        @endif

        <section class="space-y-1.5">
            <h2 class="text-sm font-semibold text-frost-900">{{ __('legal.imprint.provider_heading') }}</h2>
            <p class="text-sm leading-relaxed text-frost-600">{{ \App\Support\Legal::text('imprint.provider_note') }}</p>
        </section>

        <section class="space-y-1.5">
            <h2 class="text-sm font-semibold text-frost-900">{{ __('legal.imprint.contact_heading') }}</h2>

            <x-legal-contact class="mt-2" :email-label="__('legal.imprint.email_label')" />
        </section>

        <x-legal-sections :sections="\App\Support\Legal::sections('imprint')" />

        <x-legal-updated />
    </article>
@endsection
