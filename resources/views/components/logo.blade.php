@props(['variant' => 'default'])

@php
    /*
     | The word mark of the app: two snowflakes next to "cool" in the brand colour
     | and "ventory" in the strong text colour - exactly like the logo of the
     | project.
     |
     | Everything is measured in "em", so whoever uses the logo decides how big it
     | gets by passing a text size class ("text-base" in the header, "text-2xl" on
     | the login screen). The "on-dark" variant is for the always dark auth pages,
     | where the dark "ventory" of the default variant would disappear.
     */
    $coolClass = $variant === 'on-dark' ? 'text-brand-300' : 'text-brand-500';
    $restClass = $variant === 'on-dark' ? 'text-white' : 'text-frost-900';
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-2 font-bold leading-none tracking-tight']) }}
      role="img" aria-label="Coolventory">
    {{-- The two snowflakes of the mark: the big one on the top right, the small
         one below it on the left. --}}
    <span class="relative inline-flex h-[1.6em] w-[1.6em] shrink-0" aria-hidden="true">
        <x-icon name="snowflake" class="absolute right-0 top-0 h-[1.2em] w-[1.2em] {{ $coolClass }}" />
        <x-icon name="snowflake" class="absolute bottom-0 left-0 h-[0.75em] w-[0.75em] {{ $coolClass }}" />
    </span>

    <span><span class="{{ $coolClass }}">cool</span><span class="{{ $restClass }}">ventory</span></span>
</span>
