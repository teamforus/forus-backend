<?php

return [
    'enabled'           => env('FILES_SERVICE_ENABLED', false),
    'filesystem_driver' => env('FILES_STORAGE_DRIVER', 'local'),
    'storage_path'      => env('FILES_STORAGE_PATH', 'files'),

    // max file size in kB
    'max_file_size'     => env('FILES_MAX_SIZE', 2000),

    'allowed_extensions'     => [
        'jpg', 'jpeg', 'png', 'pdf'
    ],
    'allowed_types' => [
        'fund_request_record_proof'
    ],
];
