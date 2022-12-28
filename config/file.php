<?php

use Illuminate\Support\Env;

return [
    'enabled' => Env::get('FILES_SERVICE_ENABLED', false),
    'filesystem_driver' => Env::get('FILES_STORAGE_DRIVER', 'local'),
    'storage_path' => Env::get('FILES_STORAGE_PATH', 'files'),

    // max file size in kB
    'max_file_size' => Env::get('FILES_MAX_SIZE', 10000),

    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx',],

    'allowed_types' => [
        'fund_request_record_proof',
        'fund_request_clarification_proof',
        'reimbursement_proof',
    ],

    'allowed_extensions_per_type' => [
        'reimbursement_proof' => ['jpg', 'jpeg', 'png', 'pdf'],
    ],

    'allowed_size_per_type' => [
        'reimbursement_proof' => 8000,
    ],
];
