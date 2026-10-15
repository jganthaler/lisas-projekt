<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Die Rechtsseiten der Anwendung: Impressum, Datenschutzerklärung und
 * Nutzungsbedingungen.
 *
 * Sie sind bewusst einfache Blade-Seiten und keine Livewire-Komponenten: Es
 * gibt nichts zu bedienen, und sie müssen ohne Anmeldung lesbar sein - vor der
 * Registrierung und nach dem Löschen eines Kontos. Die Texte liegen in
 * lang/<sprache>/legal.php, die Angaben zum Anbieter in config/legal.php
 * (siehe App\Support\Legal).
 */
class LegalController extends Controller
{
    public function imprint(): View
    {
        return view('legal.imprint', ['title' => 'legal.imprint.title']);
    }

    public function privacy(): View
    {
        return view('legal.privacy', ['title' => 'legal.privacy.title']);
    }

    public function terms(): View
    {
        return view('legal.terms', ['title' => 'legal.terms.title']);
    }
}
