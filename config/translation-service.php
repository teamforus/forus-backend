<?php

return [
    'source_language' => env('TRANSLATION_SERVICE_SOURCE', 'nl'),
    'target_languages' => explode(',', env('TRANSLATION_SERVICE_TARGETS', 'en,ar')),

    // 'deepl' or 'debug'
    'provider' => env('TRANSLATION_SERVICE_PROVIDER', 'debug'),

    // Updated models and columns structure
    'models' => [
        \App\Models\Tag::class => ['name'],
        \App\Models\RecordType::class => ['name'],
        \App\Models\BusinessType::class => ['name'],
        \App\Models\ProductCategory::class => ['name'],
        \App\Models\RecordTypeOption::class => ['name'],
    ],

    'deepl' => [
        'free' => env('TRANSLATION_SERVICE_DEEPL_FREE', false),
        'api_key' => env('TRANSLATION_SERVICE_DEEPL_KEY'),
        'batch_size' => env('TRANSLATION_SERVICE_DEEPL_BATCH_SIZE', 250),
    ],

    'translations_map' => [
        'en' => 'en-US',
    ],

    'log_channel' => env('TRANSLATION_SERVICE_LOG_CHANNEL', 'translate-service'),
    'log_translations' => env('TRANSLATION_SERVICE_LOG_TRANSLATIONS', false),

    'price_per_mil' => env('TRANSLATION_SERVICE_PRICE_PER_MIL', 200),
    'max_monthly_limit' => env('TRANSLATION_SERVICE_MAX_MONTHLY_LIMIT', 100_000_000),
];
