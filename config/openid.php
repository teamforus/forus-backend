<?php

return [
    'enabled' => env('OPENID_ENABLED', false),
    'log_channel' => env('OPENID_LOG_CHANNEL', 'openid'),
    'log_raw_response' => env('OPENID_LOG_RAW_RESPONSE', false),
    'log_exception_messages' => env('OPENID_LOG_EXCEPTION_MESSAGES', false),
];
