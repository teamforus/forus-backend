<?php

return [
    'process_requests' => [
        'chunk_size' => env('PROCESS_PREVALIDATION_REQUEST_CHUNK_SIZE', 100),
        'sleep_seconds' => env('PROCESS_PREVALIDATION_REQUEST_SLEEP_SECONDS', 10),
    ],
];
