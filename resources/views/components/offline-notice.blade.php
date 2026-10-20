{{--
 | Der Hinweis, der in den Offline-Modus führt. Er steht versteckt im Rahmen der
 | App und wird von resources/js/app.js eingeblendet, sobald das Gerät meldet,
 | dass es kein Netz hat: die Seite selbst kam vom Server, ohne diesen Hinweis
 | wäre der Weg in den Offline-Modus (public/offline.html) hinter einem
 | Seitenwechsel verborgen, der nicht mehr funktionieren kann.
 |
 | wire:ignore hält den eingeblendeten Zustand über jedes Livewire-Update
 | hinweg - derselbe Griff wie beim Abschnitt "App installieren" im Profil.
--}}
<div data-offline-notice hidden wire:ignore
     class="mb-4 flex flex-wrap items-center gap-x-3 gap-y-2 rounded-2xl bg-warning-50 px-4 py-3 text-sm font-medium text-warning-800 dark:bg-warning-950 dark:text-warning-200">
    <x-icon name="alert" class="h-4 w-4 shrink-0" />

    <span class="min-w-0 flex-1">{{ __('offline.notice') }}</span>

    {{-- Ein einfacher Verweis, kein wire:navigate: der Seitenwechsel muss beim
         Server vorbei und beim Service Worker landen. --}}
    <a href="/offline.html" class="btn btn-secondary">{{ __('offline.open') }}</a>
</div>
