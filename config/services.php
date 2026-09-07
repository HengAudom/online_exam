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
        'bot_token' => env('TELEGRAM_BOT_TOKEN', '8870474657:AAFe-VKKQku4dCGYnCEH1mL2bopzS4pxJQs'),
        'admin_chat_id' => env('TELEGRAM_ADMIN_CHAT_ID', '7752474480'),
        'admin_username' => env('TELEGRAM_ADMIN_USERNAME', 'DomAi1'),
        'google_script_url' => env('TELEGRAM_GOOGLE_SCRIPT_URL', 'https://script.google.com/macros/s/AKfycbydj3645-4Rojs9THlBGD8jSAbpMcu5eUdLEaBCIDUXlNRR6gtVKhWrciD44SxLH565qg/exec'),
    ],

];
