@props(['user' => null, 'size' => 'h-9 w-9'])

@php
    $user = $user ?? auth()->user();
    $url = $user?->avatarUrl();
@endphp

{{-- Das Bild trägt seinen Rückfall bei sich: Der Kreis mit den Initialen liegt
     verborgen darunter und tritt an seine Stelle, sobald /avatars/{user} nicht
     geladen werden kann - ohne Netz, mit einer verlorenen Datei oder mit einer
     abgelaufenen Sitzung. Statt eines kaputten Rahmens des Browsers steht dann
     wieder die Standarddarstellung da, und das gespeicherte Bild bleibt
     unberührt: diese Datei löscht nichts.
     Das "onerror" steht als Attribut und nicht in einem Skript der Seite: Es
     muss auch dann greifen, wenn das Bild schon scheitert, bevor das Skript
     geladen ist. --}}
<span class="{{ $size }} relative block shrink-0 rounded-full ring-2 ring-surface">
    <span data-avatar-fallback class="absolute inset-0 rounded-full bg-brand-600" {{ $url ? 'hidden' : '' }}>
        <span class="absolute inset-0 grid place-items-center text-xs font-semibold text-white">
            {{ $user?->initials() ?? '?' }}
        </span>
    </span>

    @if ($url)
        <img src="{{ $url }}" alt="{{ $user->displayName() }}"
             onerror="this.remove(); this.previousElementSibling.hidden = false;"
             class="{{ $size }} relative rounded-full object-cover">
    @endif
</span>
