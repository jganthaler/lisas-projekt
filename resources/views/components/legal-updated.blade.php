{{--
 | Der Fuß jedes Rechtstextes: die Kontaktmöglichkeit und - sobald in der
 | Konfiguration hinterlegt - das Datum der letzten Änderung.
--}}
<footer class="space-y-1 border-t border-frost-200 pt-4 text-xs leading-relaxed text-frost-500">
    <p>{{ \App\Support\Legal::text('shared.contact') }}</p>

    @if (\App\Support\Legal::updatedAtLabel())
        <p>{{ \App\Support\Legal::text('shared.updated') }}</p>
    @endif
</footer>
