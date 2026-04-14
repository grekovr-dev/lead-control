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

    'telegram' => [
        'notifications_enabled' => env('TELEGRAM_NOTIFICATIONS_ENABLED', true),
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'manager_chat_id' => env('TELEGRAM_MANAGER_CHAT_ID'),
        'lead_url_base' => env('TELEGRAM_LEAD_URL_BASE'),
        'base_url' => env('TELEGRAM_BASE_URL', 'https://api.telegram.org'),
        'timeout_seconds' => (int) env('TELEGRAM_TIMEOUT_SECONDS', 10),
    ],

    'google_tag_manager' => [
        'id' => env('GOOGLE_TAG_MANAGER_ID'),
    ],

];
