<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Die Rechtsseiten der Anwendung: Impressum, Datenschutzerklärung und
 * Nutzungsbedingungen.
 *
 * Die Texte selbst liegen in lang/<sprache>/legal.php; die Kontaktangabe (und
 * die Angaben zu Hosting und Mail) stehen in config/legal.php, weil sie
 * unabhängig von der Sprache sind. Diese Klasse bringt beides zusammen: Sie
 * füllt die Platzhalter der Texte (":app", ":site", ":operator_email", ":hosting",
 * ":date", …) und sagt, ob die Kontaktangabe des Impressums hinterlegt ist.
 */
final class Legal
{
    /**
     * Ein einzelner Text aus lang/<sprache>/legal.php mit eingesetzten
     * Platzhaltern, zum Beispiel Legal::text('imprint.intro').
     */
    public static function text(string $key): string
    {
        return self::replace((string) __('legal.'.$key));
    }

    /**
     * Die Abschnitte eines Dokuments ("imprint", "privacy", "terms") mit
     * eingesetzten Platzhaltern. Jeder Abschnitt hat eine Überschrift und
     * mindestens einen Absatz, dazu optional eine Liste.
     *
     * @return list<array{heading: string, body?: list<string>, list?: list<string>, note?: string}>
     */
    public static function sections(string $document): array
    {
        /** @var array<int, array<string, mixed>> $sections */
        $sections = __('legal.'.$document.'.sections');

        return array_values(array_map(
            fn (array $section): array => self::replaceInSection($section),
            $sections,
        ));
    }

    /**
     * Die Platzhalter, die in den Texten vorkommen dürfen. Sie werden vor dem
     * Rendern ersetzt - ein stehen gebliebenes ":operator_email" wäre ein
     * Fehler (tests/Feature/LegalPagesTest.php prüft das).
     *
     * @return array<string, string>
     */
    public static function placeholders(): array
    {
        return [
            ':app' => (string) config('app.name'),
            ':site' => self::siteUrl() ?? __('legal.shared.placeholders.site'),
            ':operator_email' => self::operatorEmail() ?? __('legal.shared.placeholders.operator_email'),
            ':hosting' => self::hostingProvider() ?? __('legal.shared.placeholders.hosting'),
            ':hosting_location' => self::hostingLocation() ?? __('legal.shared.placeholders.location'),
            ':mail_provider' => self::mailProvider() ?? __('legal.shared.placeholders.mail_provider'),
            ':mail_location' => self::mailLocation() ?? __('legal.shared.placeholders.location'),
            ':date' => self::updatedAtLabel() ?? __('legal.shared.placeholders.date'),
        ];
    }

    public static function operatorEmail(): ?string
    {
        return self::value('legal.operator.email');
    }

    /**
     * Die öffentliche Adresse des Angebots (LEGAL_SITE_URL), wie sie im
     * Geltungsbereich des Impressums steht. Bewusst nicht APP_URL: das ist die
     * Adresse der laufenden Installation und lokal "http://localhost:8000".
     */
    public static function siteUrl(): ?string
    {
        return self::value('legal.site');
    }

    public static function hostingProvider(): ?string
    {
        return self::value('legal.hosting.provider');
    }

    public static function hostingLocation(): ?string
    {
        return self::value('legal.hosting.location');
    }

    public static function mailProvider(): ?string
    {
        return self::value('legal.mail.provider');
    }

    public static function mailLocation(): ?string
    {
        return self::value('legal.mail.location');
    }

    /**
     * Datum der letzten Änderung der Rechtstexte (LEGAL_UPDATED_AT), null wenn
     * keines hinterlegt ist.
     */
    public static function updatedAt(): ?Carbon
    {
        $value = self::value('legal.updated_at');

        if ($value === null) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Exception) {
            // Ein unbrauchbares Datum darf keine Seite zerlegen.
            return null;
        }
    }

    /**
     * Das Datum, wie es unter den Texten steht (15.10.2026).
     */
    public static function updatedAtLabel(): ?string
    {
        return self::updatedAt()?->format('d.m.Y');
    }

    /**
     * Ob die Kontaktangabe des Impressums hinterlegt ist: die E-Mail-Adresse.
     * Name und Anschrift gibt es bewusst nicht - für ein privates, nicht
     * kommerzielles Angebot verlangt italienisches Recht keine Anbieterangaben.
     */
    public static function isComplete(): bool
    {
        return self::missingFields() === [];
    }

    /**
     * Die Kontaktangabe, die noch fehlt - als Überschrift für den Warnhinweis
     * des Impressums (leer, sobald sie hinterlegt ist).
     *
     * @return list<string>
     */
    public static function missingFields(): array
    {
        $required = [
            'legal.shared.missing.email' => self::operatorEmail(),
        ];

        $missing = [];

        foreach ($required as $key => $value) {
            if ($value === null) {
                $missing[] = __($key);
            }
        }

        return $missing;
    }

    /**
     * @param  array<string, mixed>  $section
     * @return array<string, mixed>
     */
    private static function replaceInSection(array $section): array
    {
        foreach ($section as $key => $value) {
            $section[$key] = is_array($value)
                ? array_values(array_map(fn (string $line): string => self::replace($line), $value))
                : self::replace((string) $value);
        }

        return $section;
    }

    private static function replace(string $text): string
    {
        $placeholders = self::placeholders();

        // Die längeren Platzhalter zuerst: ":hosting" ist ein Präfix von
        // ":hosting_location" und würde sonst mitten in den längeren Namen
        // greifen.
        uksort($placeholders, fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        return Str::replace(array_keys($placeholders), array_values($placeholders), $text);
    }

    /**
     * Ein Wert aus der Konfiguration, wenn er tatsächlich gefüllt ist: Ein
     * leerer Eintrag in der .env ("LEGAL_OPERATOR_EMAIL=") zählt wie "nicht
     * hinterlegt".
     */
    private static function value(string $key): ?string
    {
        $value = trim((string) config($key));

        return $value === '' ? null : $value;
    }
}
