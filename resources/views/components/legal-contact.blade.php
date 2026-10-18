{{--
 | Der Kontakt des Anbieters im Impressum und in der Datenschutzerklärung: die
 | E-Mail-Adresse. Sie steht in config/legal.php (LEGAL_OPERATOR_EMAIL), damit
 | sie nicht in jedem Sprachtext gepflegt werden muss.
 |
 | Name und Anschrift stehen bewusst nicht hier: Für ein privates, nicht
 | kommerzielles Angebot verlangt italienisches Recht keine Anbieterangaben
 | (Art. 7 D.Lgs. 70/2003 und Art. 49 Codice del Consumo treffen gewerbliche
 | Dienste). Die E-Mail-Adresse genügt als schnelle elektronische
 | Kontaktaufnahme.
 |
 | Die Beschriftung kommt als Prop herein, weil die beiden Seiten, die diesen
 | Kontakt zeigen (Impressum und Datenschutzerklärung), ihn in ihrer eigenen
 | Sprache beschriften.
--}}
@props(['emailLabel' => null])

@php
    $email = \App\Support\Legal::operatorEmail();
@endphp

<address class="text-sm not-italic leading-relaxed text-frost-700">
    @if ($email)
        <span class="block">
            @if ($emailLabel)
                <span class="text-frost-500">{{ $emailLabel }}:</span>
            @endif
            <a href="mailto:{{ $email }}" class="font-medium text-brand-700 dark:text-brand-400">{{ $email }}</a>
        </span>
    @endif
</address>
