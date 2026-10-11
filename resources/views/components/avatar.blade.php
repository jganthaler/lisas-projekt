@props(['user' => null, 'size' => 'h-9 w-9'])

@php
    $user = $user ?? auth()->user();
@endphp

@if ($user && $user->avatarUrl())
    <img src="{{ $user->avatarUrl() }}" alt="{{ $user->displayName() }}"
         class="{{ $size }} shrink-0 rounded-full object-cover ring-2 ring-white">
@else
    <span class="{{ $size }} grid shrink-0 place-items-center rounded-full bg-brand-600 text-xs font-semibold text-white ring-2 ring-white">
        {{ $user?->initials() ?? '?' }}
    </span>
@endif
