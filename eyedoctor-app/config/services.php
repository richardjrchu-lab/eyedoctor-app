<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'fastapi' => [
        'url' => env('FASTAPI_URL', 'http://localhost:8080'),
        'api_key' => env('RETINA_API_KEY'),
        // Staged rollout: while false, a response with no Master 11 fields
        // at all (backend not yet upgraded) is accepted without a modality
        // check. Set true once the Master 11 backend is live, so a missing
        // modality check then fails closed.
        'require_master11' => (bool) env('RETINA_REQUIRE_MASTER11', false),
    ],
    'retention' => [
        'trigger_secret' => env(
            'RETENTION_TRIGGER_SECRET'
        ),
    ],
];
