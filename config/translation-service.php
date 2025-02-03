<?php

return [
    'source_language' => env('TRANSLATION_SERVICE_SOURCE', 'nl'),
    'target_languages' => explode(',', env('TRANSLATION_SERVICE_TARGETS', 'en,ar')),

    // 'deepl' or 'debug'
    'provider' => env('TRANSLATION_SERVICE_PROVIDER', 'debug'),

    // Updated models and columns structure
    'models' => [
        \App\Models\RecordType::class => ['name'],
        \App\Models\BusinessType::class => ['name'],
        \App\Models\ProductCategory::class => ['name'],
    ],

    'deepl' => [
        'api_key' => env('TRANSLATION_SERVICE_DEEPL_KEY'),
        'free' => env('TRANSLATION_SERVICE_DEEPL_FREE', false),
    ],

    'translations_map' => [
        'en' => 'en-US',
    ],

    'log_channel' => env('TRANSLATION_SERVICE_LOG_CHANNEL', 'translate-service'),
    'price_per_mil' => env('TRANSLATION_SERVICE_PRICE_PER_MIL', 200),
    'max_monthly_limit' => env('TRANSLATION_SERVICE_MAX_MONTHLY_LIMIT', 100_000_000),
];
