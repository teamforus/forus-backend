<?php

return [
    'default' => env('PERSON_BSN_API_SERVICE', 'iconnect'),
    'fund_prefill_cache_time' => env('PERSON_BSN_FUND_PREFILL_CACHE_TIME', 60 * 15),
    'test_response' => [
        'bsn' => 159859037,
        'name' => 'John Doe',
        'salary_if_age_30' => 700,
        'salary_if_age_45' => 600,
        'salary_if_age_50' => 500,
    ],
];
