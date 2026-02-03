<?php

return [
    'record_groups' => [
        'cache_name' => 'fund_request_record_groups',
        'cache_time' => env('FUND_REQUEST_RECORD_GROUPS_CACHE_TIME', 60 * 60 * 24),
    ],
];
