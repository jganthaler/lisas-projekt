{{--
 | Ein Abschnitt eines Rechtstextes: Überschrift, Absätze, optionale Liste und
 | optionale Schlussanmerkung. Die Struktur kommt aus lang/<sprache>/legal.php
 | und wird von App\Support\Legal::sections() mit den Platzhaltern gefüllt, die
 | aus config/legal.php stammen (Anbieter, Hoster, Datum).
--}}
@props(['sections' => []])

<div class="space-y-5">
    @foreach ($sections as $section)
        <section class="space-y-1.5">
            <h2 class="text-sm font-semibold text-frost-900">{{ $section['heading'] }}</h2>

            @foreach ($section['body'] ?? [] as $paragraph)
                <p class="text-sm leading-relaxed text-frost-600">{{ $paragraph }}</p>
            @endforeach

            @if (! empty($section['list']))
                <ul class="space-y-1.5 text-sm leading-relaxed text-frost-600">
                    @foreach ($section['list'] as $item)
                        <li class="flex gap-2">
                            <span class="mt-[0.45rem] h-1.5 w-1.5 shrink-0 rounded-full bg-brand-600 dark:bg-brand-400"
                                  aria-hidden="true"></span>
                            <span>{{ $item }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if (! empty($section['note']))
                <p class="text-xs leading-relaxed text-frost-500">{{ $section['note'] }}</p>
            @endif
        </section>
    @endforeach
</div>
