<?php

return [
    // The source language for translations.
    'source_language' => env('TRANSLATION_SERVICE_SOURCE', 'nl'),

    // Target languages for translations.
    'target_languages' => explode(',', env('TRANSLATION_SERVICE_TARGETS', 'en,ar')),

    // Translation provider to use: 'deepl' or 'debug'.
    'provider' => env('TRANSLATION_SERVICE_PROVIDER', 'debug'),

    // Models and their columns that require translation.
    'models' => [
        \App\Models\Tag::class => ['name'],
        \App\Models\RecordType::class => ['name'],
        \App\Models\BusinessType::class => ['name'],
        \App\Models\ProductCategory::class => ['name'],
        \App\Models\RecordTypeOption::class => ['name'],
    ],

    // Configuration for the DeepL translation service.
    'deepl' => [
        'free' => env('TRANSLATION_SERVICE_DEEPL_FREE', false),
        'api_key' => env('TRANSLATION_SERVICE_DEEPL_KEY'),
        'batch_size' => env('TRANSLATION_SERVICE_DEEPL_BATCH_SIZE', 250),
    ],

    // Language code mappings for external services.
    'translations_map' => [
        'en' => 'en-US',
    ],

    // Logging configuration.
    'log_channel' => env('TRANSLATION_SERVICE_LOG_CHANNEL', 'translate-service'),
    'log_translations' => env('TRANSLATION_SERVICE_LOG_TRANSLATIONS', false),

    // Pricing and usage limits.
    'price_per_mil' => env('TRANSLATION_SERVICE_PRICE_PER_MIL', 200),
    'max_monthly_limit' => env('TRANSLATION_SERVICE_MAX_MONTHLY_LIMIT', 100_000_000),

    // Caching settings.
    'cache_enabled' => env('TRANSLATION_SERVICE_CACHE_ENABLED', true),
    'cache_driver' => env('TRANSLATION_SERVICE_CACHE_DRIVER', 'file'),
    'cache_time' => env('TRANSLATION_SERVICE_CACHE_TIME', 900), // Cache in seconds
];
