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

    'minedrop' => [
        'api_url' => env('MINEDROP_API_URL'),
        'api_key' => env('MINEDROP_API_KEY'),
    ],
    'telegram' => [
        'token' => env('TELEGRAM_TOKEN'),
    ],

    'crypto_pay' => [
        'token' => env('CRYPTO_PAY_TOKEN'),
        'is_testnet' => env('CRYPTO_PAY_TESTNET', true),
    ],

    'cryptura' => [
        'api_url' => env('CRYPTURA_API_URL', 'https://cryptura.space'),
        'api_key' => env('CRYPTURA_API_KEY'),
        'username' => env('CRYPTURA_USERNAME'),
    ],

];
