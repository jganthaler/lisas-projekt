@extends('layouts.legal', ['title' => 'legal.terms.title'])

@section('content')
    <article class="card space-y-5 p-5">
        <header>
            <h1 class="text-lg font-bold text-frost-900">{{ __('legal.terms.heading') }}</h1>
            <p class="mt-1 text-sm text-frost-500">{{ \App\Support\Legal::text('terms.intro') }}</p>
        </header>

        <x-legal-sections :sections="\App\Support\Legal::sections('terms')" />

        <x-legal-updated />
    </article>
@endsection
