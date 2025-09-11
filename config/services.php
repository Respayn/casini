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
        'client_id' => env('YANDEX_DIRECT_CLIENT_ID'),
        'client_secret' => env('YANDEX_DIRECT_CLIENT_SECRET'),
        'redirect_uri' => env('YANDEX_DIRECT_REDIRECT_URI', 'https://oauth.yandex.ru/verification_code'),

        'api_url' => env('YANDEX_DIRECT_API_URL', 'https://api.direct.yandex.com/v5/json/'),
        'sandbox_api_url' => env('YANDEX_DIRECT_SANDBOX_API_URL', 'https://api-sandbox.direct.yandex.com/v5/json/'),

        'test_token' => env('YANDEX_DIRECT_TEST_TOKEN'),
        'test_client_login' => env('YANDEX_DIRECT_TEST_CLIENT_LOGIN'),

        'v4_api_url' => 'https://api.direct.yandex.ru/live/v4/json/',
        'v4_sandbox_url' => 'https://api-sandbox.direct.yandex.ru/live/v4/json/',

        'use_sandbox' => env('YANDEX_DIRECT_USE_SANDBOX', false),
    ],

    'yandex_metrika' => [
        'client_id' => env('YANDEX_METRIKA_CLIENT_ID'),
        'client_secret' => env('YANDEX_METRIKA_CLIENT_SECRET'),
        'redirect_uri' => env('YANDEX_METRIKA_REDIRECT_URI'),

        // API параметры
        'api_url' => env('YANDEX_METRIKA_API_URL', 'https://api-metrika.yandex.net/'),


        'test_token' => env('YANDEX_METRIKA_TEST_TOKEN'),
        'test_client_login' => env('YANDEX_METRIKA_TEST_CLIENT_LOGIN'),
        'test_counter_id' => env('YANDEX_METRIKA_TEST_COUNTER_ID'),

        // Настройки запросов
        'timeout' => 15,
        'cache_ttl' => 3600,
    ],

    'callibri' => [
        'api_url' => env('CALLIBRI_API_URL', 'https://api.callibri.ru/'),

        'test_email' => env('CALLIBRI_API_TEST_EMAIL'),
        'test_token' => env('CALLIBRI_API_TEST_TOKEN'),
        'test_site_id' => env('CALLIBRI_API_TEST_SITE_ID'),

        'retries' => 3,
        'timeout' => 15
    ],

    'yandex' => [
        'smartcaptcha' => [
            'enabled' => (bool) env('YANDEX_SMARTCAPTCHA_ENABLED', false),
            'client_key' => env('YANDEX_SMARTCAPTCHA_CLIENT_KEY'),
            'server_key' => env('YANDEX_SMARTCAPTCHA_SERVER_KEY')
        ]
    ]
];
