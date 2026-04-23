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

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'available_models' => [
            [
                'name' => 'Gemini 2.5 Flash',
                'id' => 'gemini-2.5-flash',
                'use_case' => 'primary_fast',
            ],
            [
                'name' => 'Gemini 2.5 Flash-Lite',
                'id' => 'gemini-2.5-flash-lite',
                'use_case' => 'simple_ocr_low_latency',
            ],
            [
                'name' => 'Gemini 1.5 Flash',
                'id' => 'gemini-1.5-flash',
                'use_case' => 'capacity_fallback',
            ],
            [
                'name' => 'Gemini 2.5 Pro',
                'id' => 'gemini-2.5-pro',
                'use_case' => 'complex_reasoning',
            ],
            [
                'name' => 'Gemini 1.5 Pro',
                'id' => 'gemini-1.5-pro',
                'use_case' => 'stable_high_context_fallback',
            ],
        ],
        'fallback_models' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('GEMINI_FALLBACK_MODELS', 'gemini-2.5-flash-lite,gemini-flash-lite-latest,gemini-2.5-pro')),
        ))),
        'ocr_model' => env('GEMINI_OCR_MODEL', 'gemini-2.5-flash-lite'),
        'complex_model' => env('GEMINI_COMPLEX_MODEL', 'gemini-2.5-pro'),
        'complex_fallback_model' => env('GEMINI_COMPLEX_FALLBACK_MODEL', 'gemini-2.5-flash'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'timeout' => (int) env('GEMINI_TIMEOUT', 30),
        'retry_attempts' => (int) env('GEMINI_RETRY_ATTEMPTS', 5),
        'retry_initial_backoff_ms' => (int) env('GEMINI_RETRY_INITIAL_BACKOFF_MS', 1000),
        'retry_max_backoff_ms' => (int) env('GEMINI_RETRY_MAX_BACKOFF_MS', 10000),
    ],

];
