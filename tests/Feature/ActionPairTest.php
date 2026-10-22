<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Eine Zeile, die zwischen „Abbrechen“ und einer Aktion wählen lässt, endet
 * überall gleich: „Abbrechen“ (`common.cancel`) links, die Hauptaktion rechts -
 * „Speichern“, „Erstellen“ oder „Verschieben“ -, und beide Knöpfe sind genau
 * gleich groß. Die Zeile ist ein Gitter aus zwei gleich breiten Spalten
 * (`grid grid-cols-2 gap-2`, jeder Knopf `w-full`) und keine Flex-Zeile mit
 * `flex-1` auf einem der beiden: dort hinge die Breite an der Länge der
 * Beschriftung, und „Abbrechen“ und „Speichern“ sind nicht dasselbe Wort.
 *
 * Die Ordnung im Markup ist die Ordnung auf dem Bildschirm, deshalb wacht dieser
 * Test über die Ansichten selbst: er sucht jede Zeile der App, die abbrechen
 * lässt, und prüft, wo die Aktion steht, wie breit die beiden Knöpfe sind und
 * woraus ihre Zeile gebaut ist. Eine Zeile, die die Hauptaktion nach links
 * stellt, fällt hier auf - und ebenso eine, die ihre Breite wieder über
 * `flex-1` verteilt.
 *
 * Die Rückfrage vor dem Löschen behält ihre eigene Zeile
 * (`components/confirm-dialog.blade.php`): sie ist kein Formular, sondern ein
 * Dialog, und seine beiden Knöpfe stehen auf dem Telefon untereinander und erst
 * ab `sm` nebeneinander - in umgekehrter Reihenfolge, damit die Antwort rechts
 * steht. Der zweite Test unten hält genau das fest.
 */
class ActionPairTest extends TestCase
{
    /** Die Zeile, die ihre eigene Gestalt behält. */
    private const OWN_ROW = 'components/confirm-dialog.blade.php';

    /** Der Knopf der Aktion: die Hauptaktion oder die, die löscht. */
    private const ACTION = 'btn-primary|btn-danger';

    public function test_every_cancel_stands_left_of_the_action_in_two_equal_columns(): void
    {
        $problems = [];
        $handlers = [];

        foreach (File::allFiles(resource_path('views')) as $view) {
            // Der Pfad kommt vom Dateisystem: unter Windows mit umgekehrten
            // Trennzeichen, deshalb wird er verglichen, wie er geschrieben ist.
            $name = str_replace('\\', '/', $view->getRelativePathname());

            if ($name === self::OWN_ROW) {
                continue;
            }

            $contents = File::get($view->getPathname());

            $problems = [...$problems, ...$this->problems($contents, $name)];
            $handlers = [...$handlers, ...$this->handlers($contents)];
        }

        $this->assertSame([], $problems, implode("\n", $problems));

        // Die Zeilen, über die dieser Test wacht: die Liste hält fest, dass sein
        // Blick wirklich auf ihnen liegt und dass keine davon verschwunden ist.
        sort($handlers);

        $this->assertSame(
            ['cancelAddingFreezer', 'cancelEditingItem', 'cancelMovingItem', 'cancelRenaming', 'cancelRenaming'],
            $handlers,
            'Diese Zeilen bieten ein Abbrechen an: '.implode(', ', $handlers)
        );
    }

    /**
     * Der Dialog ist keine Zeile aus zwei Spalten, aber auch er hält die Antwort
     * auf der rechten Seite: seine Knöpfe stehen ab „sm“ nebeneinander, und zwar
     * in umgekehrter Reihenfolge. Der Antwort-Knopf steht im Markup zuerst, weil
     * der Fokus dort hin gehört (`$refs.confirm.focus()`).
     */
    public function test_the_dialog_of_the_app_keeps_its_answer_on_the_right(): void
    {
        $dialog = File::get(resource_path('views/'.self::OWN_ROW));

        $this->assertStringContainsString(
            'flex flex-col gap-2 sm:flex-row-reverse',
            $dialog,
            'Der Dialog der App dreht seine Knöpfe nicht: die Antwort stünde links.'
        );

        $this->assertLessThan(
            strpos($dialog, "__('common.cancel')"),
            strpos($dialog, 'x-ref="confirm"'),
            'Der Dialog stellt die Antwort nicht auf die rechte Seite.'
        );
    }

    /**
     * Die Prüfung oben bedeutet nur etwas, wenn sie eine Zeile erkennt, die die
     * Hauptaktion nach links stellt - und wenn sie die Ansichten überhaupt sieht.
     */
    public function test_the_checks_catch_a_row_that_puts_the_action_left(): void
    {
        $offender = '<div class="flex gap-2">'
            .'<button type="submit" class="btn btn-primary flex-1">{{ __(\'common.save\') }}</button>'
            .'<button type="button" class="btn btn-ghost">{{ __(\'common.cancel\') }}</button>'
            .'</div>';

        $this->assertNotSame([], $this->problems($offender, 'eine Ansicht'));
        $this->assertNotEmpty(File::allFiles(resource_path('views')));
    }

    /**
     * Was an einer Ansicht nicht stimmt - leer, wenn jede Zeile stimmt.
     *
     * @return list<string>
     */
    private function problems(string $view, string $file): array
    {
        $problems = [];

        foreach ($this->cancelButtons($view) as [$button, $offset]) {
            // Die Zeile des Abbrechens ist das Gitter, in dem der Knopf steht.
            // Das Abbrechen selbst trägt keine weiteren Elemente, deshalb ist das
            // nächste `</div>` das Ende seiner Zeile.
            $start = strrpos(substr($view, 0, $offset), '<div');
            $end = strpos($view, '</div>', $offset);

            if ($start === false || $end === false) {
                $problems[] = $file.': die Zeile des Abbrechens ist keine Zeile aus zwei Knöpfen.';

                continue;
            }

            $row = substr($view, $start, $end - $start);
            $before = substr($view, $start, $offset - $start);
            $after = substr($view, $offset + strlen($button), $end - $offset - strlen($button));

            if (preg_match('/\b('.self::ACTION.')\b/', $before) === 1) {
                $problems[] = $file.': die Hauptaktion steht links vom Abbrechen.';
            }

            if (preg_match('/\b('.self::ACTION.')\b/', $after) !== 1) {
                $problems[] = $file.': hinter dem Abbrechen fehlt die Aktion.';
            }

            if (! $this->isGridOfTwoColumns($row)) {
                $problems[] = $file.': die Zeile des Abbrechens ist kein Gitter aus zwei gleich breiten Spalten (grid grid-cols-2 gap-2).';
            }

            if (str_contains($row, 'flex-1')) {
                $problems[] = $file.': die Zeile des Abbrechens verteilt die Breite über flex-1.';
            }

            if (! str_contains($this->classesOf($button), 'w-full')) {
                $problems[] = $file.': das Abbrechen nimmt nicht die halbe Breite ein (w-full fehlt).';
            }

            if (! str_contains($after, 'w-full')) {
                $problems[] = $file.': die Aktion nimmt nicht die halbe Breite ein (w-full fehlt).';
            }
        }

        return $problems;
    }

    /**
     * Teilt die Zeile zwei gleich breite Spalten?
     */
    private function isGridOfTwoColumns(string $row): bool
    {
        $classes = preg_split('/\s+/', $this->classesOf($row), -1, PREG_SPLIT_NO_EMPTY);

        return in_array('grid', $classes, true) && in_array('grid-cols-2', $classes, true);
    }

    /**
     * Das Klassen-Attribut des ersten Elements in einem Stück Markup.
     */
    private function classesOf(string $markup): string
    {
        preg_match('/class="([^"]*)"/', $markup, $classes);

        return $classes[1] ?? '';
    }

    /**
     * Jeder Knopf, der abbrechen lässt: sein Markup und seine Stelle in der
     * Ansicht.
     *
     * @return list<array{0: string, 1: int}>
     */
    private function cancelButtons(string $view): array
    {
        preg_match_all(
            '/<button\b[^>]*>\s*\{\{\s*__\(\'common\.cancel\'\)\s*\}\}\s*<\/button>/s',
            $view,
            $matches,
            PREG_OFFSET_CAPTURE
        );

        return array_map(fn (array $match): array => [$match[0], $match[1]], $matches[0]);
    }

    /**
     * Die Funktionen, die die abbrechenden Knöpfe einer Ansicht aufrufen.
     *
     * @return list<string>
     */
    private function handlers(string $view): array
    {
        $handlers = [];

        foreach ($this->cancelButtons($view) as [$button]) {
            preg_match('/wire:click="([^"]+)"/', $button, $match);

            $handlers[] = $match[1] ?? 'ohne Funktion';
        }

        return $handlers;
    }
}
