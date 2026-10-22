<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * One corner radius for every card of the app: 8 px, defined once.
 *
 * The value stands in resources/css/app.css ("--radius-card" in @theme) and
 * every card takes it from there - the component class ".card" of the app, the
 * row of a drawer (".drawer-row") and the flash on a freshly added item. A view
 * never rounds a card itself, so a card that is added tomorrow carries "card"
 * and is rounded like the ones of today.
 *
 * The offline page (public/offline.html) draws the inventory without the CSS of
 * the app, so it repeats the value; the checks below keep the two in step.
 */
class CardRadiusTest extends TestCase
{
    /** The corners of a card, in the design of the app. */
    private const CARD_RADIUS = '0.5rem';

    /** The component classes that make a card of a view. */
    private const CARD_CLASSES = ['card', 'drawer-row'];

    public function test_the_stylesheet_defines_the_corners_of_a_card_once(): void
    {
        $css = $this->stylesheet();

        $this->assertMatchesRegularExpression(
            '/--radius-card:\s*'.preg_quote(self::CARD_RADIUS, '/').'\s*;/',
            $css,
            'resources/css/app.css definiert "--radius-card" nicht (oder nicht als 8 px).'
        );

        $this->assertMatchesRegularExpression(
            '/\.card\s*\{[^}]*border-radius:\s*var\(--radius-card\);/',
            $css,
            'Die Karte ".card" nimmt ihre Ecken nicht aus "--radius-card".'
        );

        // Die Zeile einer Schublade ist eine Karte wie jede andere.
        $this->assertMatchesRegularExpression(
            '/\.drawer-row\s*\{[^}]*border-radius:\s*var\(--radius-card\);/',
            $css,
            'Die Zeile ".drawer-row" nimmt ihre Ecken nicht aus "--radius-card".'
        );

        // Der Schein auf einem neuen Artikel malt die Zeile selbst.
        $this->assertMatchesRegularExpression(
            '/\.highlight-item\s*\{[^}]*border-radius:\s*var\(--radius-card\);/',
            $css,
            'Der Schein ".highlight-item" nimmt seine Ecken nicht aus "--radius-card".'
        );
    }

    /**
     * A card carries the class of the cards and nothing else: a radius of its
     * own would be a second standard next to the one of the design. The card
     * token itself stays allowed ("rounded-t-card" for the band of a row), a
     * value of its own ("rounded-control", "rounded-2xl", "rounded-[1.25rem]")
     * does not.
     */
    public function test_no_card_of_a_view_brings_its_own_radius(): void
    {
        $cards = 0;

        foreach (File::allFiles(resource_path('views')) as $view) {
            foreach ($this->classAttributesOf(File::get($view->getPathname())) as $classes) {
                if (array_intersect(self::CARD_CLASSES, $classes) === []) {
                    continue;
                }

                $cards++;

                $own = array_values(array_filter(
                    $classes,
                    fn (string $class): bool => str_starts_with($class, 'rounded')
                        && preg_match('/(^|-)card$/', $class) !== 1
                ));

                $this->assertSame(
                    [],
                    $own,
                    'Die Karte in resources/views/'.$view->getRelativePathname()
                        .' bringt einen eigenen Eckenradius mit ('.implode(' ', $own)
                        .'): ihre Ecken kommen aus "--radius-card" (8 px).'
                );
            }
        }

        $this->assertGreaterThan(
            0,
            $cards,
            'Keine Karte in resources/views gefunden - prüft dieser Test noch die Ansichten?'
        );
    }

    /**
     * The offline page is the inventory itself and has to look like the one of
     * the app, so its cards carry the same 8 px.
     */
    public function test_the_offline_page_rounds_its_cards_like_the_app(): void
    {
        $page = $this->publicFile('offline.html');

        $this->assertMatchesRegularExpression(
            '/--radius-card:\s*'.preg_quote(self::CARD_RADIUS, '/').'\s*;/',
            $page,
            'Die Offline-Seite definiert "--radius-card" nicht (oder nicht als 8 px).'
        );

        // Die Karte des Schnappschusses und die Zeile eines Artikels.
        foreach (['card', 'item'] as $class) {
            $this->assertMatchesRegularExpression(
                '/\.'.$class.'\s*\{[^}]*border-radius:\s*var\(--radius-card\);/',
                $page,
                'Die Karte ".'.$class.'" der Offline-Seite nimmt ihre Ecken nicht aus "--radius-card".'
            );
        }
    }

    /**
     * Every class attribute of a view, each one split into its single classes.
     *
     * A binding is no class attribute and stays out: "x-bind:class" (Alpine) and
     * ":class" (Livewire) carry an expression, not a list of classes - read as a
     * list, the braces and the keys of the expression would look like classes.
     *
     * @return list<list<string>>
     */
    private function classAttributesOf(string $view): array
    {
        preg_match_all('/(?<![-\w:])class="([^"]*)"/', $view, $matches);

        $attributes = [];

        foreach ($matches[1] as $attribute) {
            $attributes[] = preg_split('/\s+/', trim($attribute), -1, PREG_SPLIT_NO_EMPTY);
        }

        return $attributes;
    }

    private function stylesheet(): string
    {
        $path = resource_path('css/app.css');

        $this->assertFileExists($path, 'Das Stylesheet resources/css/app.css fehlt.');

        return (string) file_get_contents($path);
    }

    private function publicFile(string $name): string
    {
        $path = public_path($name);

        $this->assertFileExists($path, 'Die Datei public/'.$name.' fehlt.');

        return (string) file_get_contents($path);
    }
}
