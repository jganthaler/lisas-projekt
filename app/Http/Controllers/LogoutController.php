<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Ends the session of the current user.
 */
class LogoutController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        // The whole session (including the selected freezer) is thrown away so
        // that nothing leaks into the next login.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
