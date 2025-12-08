<?php

return [
    'expire_soon_notifications' => [
        'chunk_size' => env('VOUCHER_EXPIRE_SOON_CHUNK_SIZE', 100),
        'sleep_seconds' => env('VOUCHER_EXPIRE_SOON_SLEEP_SECONDS', 10),
    ],
];
