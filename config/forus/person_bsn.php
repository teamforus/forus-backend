<?php

return [
    'default' => env('PERSON_BSN_API_SERVICE', 'iconnect'),
    'fund_prefill_cache_time' => env('PERSON_BSN_FUND_PREFILL_CACHE_TIME', 60 * 15),
    'test_response' => env('PERSON_BSN_TEST_RESPONSE', false),
    'test_response_profile' => env('PERSON_BSN_TEST_RESPONSE_PROFILE', 'default'),

    'test_response_data' => [
        'default' => include __DIR__ . '/person_bsn_profiles/default.php',
        'missed_records' => include __DIR__ . '/person_bsn_profiles/missed_records.php',
        'custom' => include __DIR__ . '/person_bsn_profiles/custom.php',
    ],
];
