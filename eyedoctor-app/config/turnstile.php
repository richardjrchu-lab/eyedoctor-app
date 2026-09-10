<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cloudflare Turnstile
    |--------------------------------------------------------------------------
    |
    | The public professional-access form must pass Cloudflare Turnstile
    | before applicant data or verification documents are processed.
    |
    */

    'site_key' => env('TURNSTILE_SITE_KEY'),

    'secret_key' => env('TURNSTILE_SECRET_KEY'),

    'expected_hostname' => env(
        'TURNSTILE_EXPECTED_HOSTNAME'
    ),

    'expected_action' => env(
        'TURNSTILE_EXPECTED_ACTION',
        'professional_access_request'
    ),

    'verify_url' =>
        'https://challenges.cloudflare.com/turnstile/v0/siteverify',

];
