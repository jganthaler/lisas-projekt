{{--
 | Anschrift und Kontaktmöglichkeit des Anbieters - die Pflichtangaben des
 | Impressums. Sie stehen in config/legal.php (LEGAL_*), damit sie nicht in
 | jedem Sprachtext gepflegt werden müssen. Fehlende Zeilen entfallen.
 |
 | Die Beschriftungen von E-Mail und Telefon kommen als Props herein, weil die
 | beiden Seiten, die diese Adresse zeigen (Impressum und
 | Datenschutzerklärung), sie in ihrer eigenen Sprache beschriften.
--}}
@props(['emailLabel' => null, 'phoneLabel' => null])

@php
    $addressLines = \App\Support\Legal::addressLines();
    $email = \App\Support\Legal::operatorEmail();
    $phone = \App\Support\Legal::operatorPhone();
@endphp

<address class="text-sm not-italic leading-relaxed text-frost-700">
    @foreach ($addressLines as $line)
        <span class="block">{{ $line }}</span>
    @endforeach

    @if ($email)
        <span class="block">
            @if ($emailLabel)
                <span class="text-frost-500">{{ $emailLabel }}:</span>
            @endif
            <a href="mailto:{{ $email }}" class="font-medium text-brand-700 dark:text-brand-400">{{ $email }}</a>
        </span>
    @endif

    @if ($phone)
        <span class="block">
            @if ($phoneLabel)
                <span class="text-frost-500">{{ $phoneLabel }}:</span>
            @endif
            {{ $phone }}
        </span>
    @endif
</address>
