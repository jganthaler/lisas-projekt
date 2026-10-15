<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets the language of a request: the one the user picked in the profile,
 * otherwise the default of the app.
 *
 * The middleware sits in the "web" group and therefore covers the pages as well
 * as the round trips of Livewire - no component has to think about the
 * language, they all just translate their texts.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale;

        // The language is always set explicitly: without a (valid) choice of the
        // account it is the default of the app - and never a leftover of a
        // former request in the same process (a test, a worker like Octane).
        App::setLocale(Locales::supports($locale) ? $locale : Locales::default());

        return $next($request);
    }
}
