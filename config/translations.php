<?php

return [
    'source_language' => env('TRANSLATION_SOURCE', 'nl'),
    'target_languages' => explode(',', env('TRANSLATION_TARGETS', 'en-US')),

    // 'deepl' or 'debug'
    'provider' => env('TRANSLATION_PROVIDER', 'debug'),

    // Updated models and columns structure
    'models' => [
        \App\Models\Role::class => ['name', 'description'],
        \App\Models\RecordType::class => ['name'],
        \App\Models\BusinessType::class => ['name'],
        \App\Models\ProductCategory::class => ['name'],
    ],

    'deepl' => [
        'api_key' => env('DEEPL_API_KEY'),
    ],

    'translations_map' => [
        'en-US' => 'en',
    ],
];
