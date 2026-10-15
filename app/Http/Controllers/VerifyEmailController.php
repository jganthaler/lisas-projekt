<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Confirms the e-mail address behind the signed link of the verification mail.
 *
 * The request validates the signature as well as the id and the hash of the
 * signed in user, so the link belongs to exactly one account and can neither be
 * guessed nor used for somebody else.
 */
class VerifyEmailController extends Controller
{
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        session()->flash('status', __('auth.verify.confirmed'));

        // Whoever was on the way somewhere (for example to an invitation) is
        // brought back there.
        return redirect()->intended(route('freezer.closed'));
    }
}
