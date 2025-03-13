<?php

use Illuminate\Support\Env;

return [
    'log_production' => Env::get('MAIL_LOG_PRODUCTION', false),
    'log_attachments' => Env::get('MAIL_LOG_ATTACHMENTS', false),

    'log_storage_driver' => env('MAIL_LOG_STORAGE_DRIVER', 'local'),
    'log_storage_path' => env('MAIL_LOG_STORAGE_PATH', 'attachments'),

    'from' => [
        'no-reply' => env('MAIL_FROM_ADDRESS', 'no-reply@forus.io'),
        'name' => env('MAIL_FROM_NAME', 'Stichting Forus'),
    ],

    'email-preferences-link' => env('EMAIL_PREFERENCES_LINK'),
    'email-not-you-link' => env('EMAIL_NOT_YOU_LINK'),
    'max_identity_emails' => env('MAX_IDENTITY_EMAILS', 4),
];
