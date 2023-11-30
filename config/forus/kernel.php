<?php

return [
    'bank_daily_bulk_build_time' => env('BANK_DAILY_BULK_BUILD_TIME', '09:00'),

    'disable_digest' => env('DISABLE_DIGEST', false),
    'disable_auth_expiration' => env('DISABLE_AUTH_EXPIRATION', false),

    'queue_use_cron' => env('QUEUE_USE_CRON', false),
    'email_queue_name' => env('EMAIL_QUEUE_NAME', 'emails'),
    'notifications_queue_name' => env('NOTIFICATIONS_QUEUE_NAME', 'push_notifications'),
];