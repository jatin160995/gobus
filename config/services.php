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

    'orange' => [
        'base_url'             => env('ORANGE_BASE_URL', 'https://api-s1.orange.cm/omcoreapis/1.0.2'),
        'token_url'            => env('ORANGE_TOKEN_URL', 'https://api-s1.orange.cm/token'),
        'consumer_key'         => env('ORANGE_CONSUMER_KEY'),
        'consumer_secret'      => env('ORANGE_CONSUMER_SECRET'),
        'merchant_msisdn'      => env('ORANGE_MERCHANT_MSISDN'),
        'channel_user_msisdn'  => env('ORANGE_CHANNEL_USER_MSISDN', '691301143'),
        'x_auth_token'         => env('ORANGE_X_AUTH_TOKEN'),
        'pin'                  => env('ORANGE_PIN', '2222'),
        'webhook_url'          => env('ORANGE_WEBHOOK_URL'),
        'env'                  => env('ORANGE_ENV', 'sandbox'),
    ],

];
