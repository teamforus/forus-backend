<?php

return [
    'enabled' => env('OPENID_ENABLED', false),
    'session_expiration_seconds' => 600,
    'session_soft_delete_after_days' => 30,
    'session_hard_delete_after_days' => null,
    'log_channel' => env('OPENID_LOG_CHANNEL', 'openid'),
    'log_raw_response' => env('OPENID_LOG_RAW_RESPONSE', false),
    'log_exception_messages' => env('OPENID_LOG_EXCEPTION_MESSAGES', false),
];
