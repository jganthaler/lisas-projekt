@extends('layouts.legal', ['title' => 'legal.privacy.title'])

@section('content')
    <article class="card space-y-5 p-5">
        <header>
            <h1 class="text-lg font-bold text-frost-900">{{ __('legal.privacy.heading') }}</h1>
            <p class="mt-1 text-sm text-frost-500">{{ \App\Support\Legal::text('privacy.intro') }}</p>
        </header>

        {{-- Der Verantwortliche im Sinne der DSGVO: dieselben Angaben wie im
             Impressum, damit Art. 13 DSGVO erfüllt ist. --}}
        <section class="space-y-1.5">
            <h2 class="text-sm font-semibold text-frost-900">{{ __('legal.privacy.controller_heading') }}</h2>
            <p class="text-sm leading-relaxed text-frost-600">{{ __('legal.privacy.controller_intro') }}</p>

            <x-legal-address class="mt-2"
                             :email-label="__('legal.imprint.email_label')"
                             :phone-label="__('legal.imprint.phone_label')" />
        </section>

        <x-legal-sections :sections="\App\Support\Legal::sections('privacy')" />

        <x-legal-updated />
    </article>
@endsection
