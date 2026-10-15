<?php

/*
 | Signing in, registering, the password reset and the reminder that the
 | e-mail address still has to be confirmed.
 |
 | "title" is the name of the browser tab, "heading" the headline of the page.
 | The hint on the verification page is built around the address of the account
 | in the view, which is why it is split into "sent_before" and "sent_after".
 */

return [
    'tagline' => 'Keep track of your freezer together.',

    'login' => [
        'title' => 'Sign in',
        'heading' => 'Welcome back',
        'subtitle' => 'Sign in to open your freezer.',
        'remember' => 'Stay signed in',
        'submit' => 'Sign in',
        'failed' => 'The email address or the password does not match.',
        'throttled' => 'Too many attempts. Please try again in a minute.',
        'no_account' => 'No account yet?',
        'register' => 'Register now',
    ],

    'register' => [
        'title' => 'Register',
        'heading' => 'Create an account',
        'subtitle' => 'Your own freezer with three drawers is already waiting for you.',
        'submit' => 'Register',
        'busy' => 'Creating your account …',
        'have_account' => 'Already registered?',
        'login' => 'Go to sign in',
        'status' => 'Almost done! We sent you a link to :email.',

        'consent' => 'By registering you agree to the :terms and the :privacy.',
    ],

    'forgot' => [
        'title' => 'Forgot password',
        'heading' => 'Forgot your password?',
        'subtitle' => 'No problem: enter your email address and we will send you a link to reset it. It stays valid for one hour.',
        'submit' => 'Send the link',
        'sent' => 'If there is an account for :email, the email with the link is on its way. Have a look in the spam folder as well.',
        'hint' => 'Nothing arrived? Wait a moment and request the link again.',
        'back_to_login' => 'Back to sign in',
        'throttled' => 'Too many requests. Please try again in a minute.',
        'already_requested' => 'A link was just requested. Please wait a minute.',
    ],

    'reset' => [
        'title' => 'New password',
        'subtitle' => 'Choose a new password for your account. You can sign in with it right after.',
        'submit' => 'Save the password',
        'invalid_link' => 'The link is invalid or has expired. Please request a new one.',
        'status' => 'Your password has been changed. Please sign in again.',
        'expired_question' => 'Link expired?',
        'request_new' => 'Request a new one',
    ],

    'verify' => [
        'title' => 'Confirm email',
        'heading' => 'Confirm your email address',
        'sent_before' => 'We sent a link to',
        'sent_after' => 'Please click it to unlock your freezer.',
        'resend' => 'Send the link again',
        'throttled' => 'Please wait a minute before requesting a new link.',
        'sent' => 'We sent you a new link to :email.',
        'confirmed' => 'Your email address is confirmed. Welcome!',
    ],
];
