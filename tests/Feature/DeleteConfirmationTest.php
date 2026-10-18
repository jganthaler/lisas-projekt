<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Deleting always asks - and it asks through the dialog of the app
 * (resources/views/components/confirm-dialog.blade.php), never through the dialog
 * of the browser: that one cannot be styled and looks foreign on a phone.
 *
 * The checks below keep that promise for the future. A new destructive button
 * that reaches for window.confirm() or wire:confirm fails here, and so does one
 * that wires the action onto itself instead of onto the dialog of the app.
 */
class DeleteConfirmationTest extends TestCase
{
    /**
     * The verbs of the actions that take something away.
     */
    private const DESTRUCTIVE = 'delete|destroy|remove|leave|revoke';

    public function test_no_view_uses_the_dialog_of_the_browser(): void
    {
        $offenders = $this->views($this->usesBrowserDialog(...));

        $this->assertSame(
            [],
            $offenders,
            'These views ask through the dialog of the browser: '.implode(', ', $offenders)
        );
    }

    /**
     * The button that starts a destructive action only opens the dialog; the
     * request itself belongs to the confirm button inside it.
     */
    public function test_no_view_wires_a_destructive_action_onto_its_own_button(): void
    {
        $offenders = $this->views($this->wiresItsOwnButton(...));

        $this->assertSame(
            [],
            $offenders,
            'These views wire a destructive action onto their own button: '.implode(', ', $offenders)
        );
    }

    /**
     * Wherever a destructive action is handed to a dialog, the dialog of the app
     * is the one that renders it.
     */
    public function test_every_destructive_action_is_handed_to_the_dialog_of_the_app(): void
    {
        $offenders = $this->views($this->handsTheActionToAnotherDialog(...));

        $this->assertSame(
            [],
            $offenders,
            'These views hand a destructive action to something other than the dialog: '.implode(', ', $offenders)
        );
    }

    /**
     * The checks above only mean something if they would catch a destructive
     * button that ignores the dialog - and if the scan sees the views at all.
     */
    public function test_the_checks_catch_a_button_that_ignores_the_dialog(): void
    {
        $offender = '<button type="button" wire:click="deleteItem(1)" wire:confirm="Wirklich löschen?">Löschen</button>';

        $this->assertTrue($this->usesBrowserDialog($offender));
        $this->assertTrue($this->wiresItsOwnButton($offender));
        $this->assertTrue($this->handsTheActionToAnotherDialog('action="deleteAccount"'));
        $this->assertNotEmpty(File::allFiles(resource_path('views')));
    }

    /**
     * Asks the browser for an answer.
     */
    private function usesBrowserDialog(string $contents): bool
    {
        return preg_match('/wire:confirm|window\.confirm|onclick="[^"]*confirm\(/', $contents) === 1;
    }

    /**
     * Calls a destructive action directly instead of opening the dialog first.
     */
    private function wiresItsOwnButton(string $contents): bool
    {
        return preg_match('/wire:click="\s*('.self::DESTRUCTIVE.')/i', $contents) === 1;
    }

    /**
     * Carries a destructive action without rendering the dialog of the app.
     */
    private function handsTheActionToAnotherDialog(string $contents): bool
    {
        return preg_match('/:?action="\'?('.self::DESTRUCTIVE.')/i', $contents) === 1
            && ! str_contains($contents, '<x-confirm-dialog');
    }

    /**
     * The names of every view whose contents match the given check.
     *
     * @param  callable(string): bool  $matches
     * @return list<string>
     */
    private function views(callable $matches): array
    {
        $offenders = [];

        foreach (File::allFiles(resource_path('views')) as $view) {
            if ($matches(File::get($view->getPathname()))) {
                $offenders[] = $view->getRelativePathname();
            }
        }

        return $offenders;
    }
}
