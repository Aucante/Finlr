<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
    'oauth_failed' => 'We could not sign you in with that provider. Please try again.',
    'oauth_account_exists' => 'An account already exists for this email address. Please sign in to that account first, then try connecting this provider again.',

    'two_factor' => [
        'mail' => [
            'subject' => 'Your login code',
            'greeting' => 'Hello!',
            'intro' => 'Here is the code to complete your sign-in.',
            'code_line' => 'Verification code: :code',
            'expiry_notice' => 'This code expires in :minutes minutes. If you did not request this, you can safely ignore this email.',
        ],
        'challenge' => [
            'invalid_code' => 'This code is incorrect or has expired.',
            'session_expired' => 'Your sign-in attempt has expired. Please sign in again.',
        ],
    ],

];
