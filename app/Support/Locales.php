<?php

namespace App\Support;

/**
 * The languages the app speaks.
 *
 * The profile offers one entry per language here and the choice is stored on
 * the account (users.locale), so that every later request can pick it up again
 * - App\Http\Middleware\SetLocale does that for the whole app.
 */
final class Locales
{
    /** The language a request starts with, as long as nobody chose one (see default()). */
    private const DEFAULT = 'de';

    /**
     * Every supported language as code => its own name. The names are
     * deliberately not translated: "Deutsch" has to be recognisable no matter
     * which language the app is shown in.
     *
     * @var array<string, string>
     */
    public const ALL = [
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'en' => 'English',
    ];

    /**
     * The codes of every supported language - what the profile form validates
     * the chosen language against.
     *
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(self::ALL);
    }

    /**
     * Whether the app speaks the given language. Anything else - an empty
     * column, an old value of a language that was dropped - counts as "nothing
     * chosen" and falls back to the default.
     */
    public static function supports(?string $locale): bool
    {
        return $locale !== null && array_key_exists($locale, self::ALL);
    }

    /**
     * Name of a language, written in the language itself ("Italiano").
     */
    public static function label(string $locale): string
    {
        return self::ALL[$locale] ?? $locale;
    }

    /**
     * The language a request starts with: guests and accounts without a valid
     * choice get it.
     *
     * Deliberately a fixed value and not config('app.locale'): App::setLocale()
     * writes into that very config key, so it must not decide the language of a
     * later request in the same process (a test, a queue worker, Octane).
     */
    public static function default(): string
    {
        return self::DEFAULT;
    }
}
