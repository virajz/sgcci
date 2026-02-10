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

    'whatsapp' => [
        'enabled' => env('WHATSAPP_ENABLED', false),
        'api_key' => env('WHATSAPP_API_KEY'),
        'api_url' => env('WHATSAPP_API_URL'),
        'username' => env('WHATSAPP_USERNAME'),
        'source' => env('WHATSAPP_SOURCE', 'booking-system'),
    ],

    'sms' => [
        'enabled' => env('SMS_ENABLED', false),
        'api_url' => env('SMS_API_URL', 'http://smsl.myappstores.com/api/mt/SendSMS'),
        'username' => env('SMS_USERNAME'),
        'password' => env('SMS_PASSWORD'),
        'sender_id' => env('SMS_SENDER_ID', 'CHAMBR'),
        'channel' => env('SMS_CHANNEL', 'Trans'),
        'dcs' => env('SMS_DCS', 0),
        'route' => env('SMS_ROUTE', 17),
    ],

    'ccavenue' => [
        'merchant_id' => env('CCAVENUE_MERCHANT_ID'),
        'access_code' => env('CCAVENUE_ACCESS_CODE'),
        'working_key' => env('CCAVENUE_WORKING_KEY'),
        'test_mode' => env('CCAVENUE_TEST_MODE', true),
        'currency' => env('CCAVENUE_CURRENCY', 'INR'),
        'redirect_url' => env('CCAVENUE_REDIRECT_URL'),
        'cancel_url' => env('CCAVENUE_CANCEL_URL'),
        'visitor_redirect_url' => env('CCAVENUE_VISITOR_REDIRECT_URL'),
        'visitor_cancel_url' => env('CCAVENUE_VISITOR_CANCEL_URL'),
    ],

];
