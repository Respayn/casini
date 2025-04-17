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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'yandex_direct' => [
        'token' => env('YANDEX_DIRECT_TOKEN'),
        'client_id' => env('YANDEX_DIRECT_CLIENT_ID'),
        'client_secret' => env('YANDEX_DIRECT_CLIENT_SECRET'),
        'client_login' => env('YANDEX_DIRECT_CLIENT_LOGIN'),
        'redirect_uri' => env('YANDEX_DIRECT_REDIRECT_URI', 'https://oauth.yandex.ru/verification_code'),

        'api_url' => env('YANDEX_DIRECT_API_URL', 'https://api.direct.yandex.com/v5/json/'),
        'sandbox_api_url' => env('YANDEX_DIRECT_SANDBOX_API_URL', 'https://api-sandbox.direct.yandex.com/v5/json/'),

        'v4_api_url' => 'https://api.direct.yandex.ru/live/v4/json/',
        'v4_sandbox_url' => 'https://api-sandbox.direct.yandex.ru/live/v4/json/',

        'use_sandbox' => env('YANDEX_DIRECT_USE_SANDBOX', false),
    ],
];
