@props(['href', 'label', 'icon', 'badge' => 0, 'active' => false])

<a href="{{ $href }}" wire:navigate @if ($active) aria-current="page" @endif
   @if ($badge > 0) aria-label="{{ __('nav.badge_aria', ['label' => $label, 'count' => $badge]) }}" @endif class="bottom-nav-link">
    <span class="relative">
        <x-icon :name="$icon" class="h-6 w-6" />

        @if ($badge > 0)
            {{-- How many items are waiting in this area, as a round counter. --}}
            <span class="nav-badge">{{ $badge }}</span>
        @endif
    </span>
    <span>{{ $label }}</span>
</a>
