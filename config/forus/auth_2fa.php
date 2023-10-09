<?php

use Illuminate\Support\Env;

return [
    'throttle_decay' => Env::get('AUTH_2FA_THROTTLE_DECAY', 30),
    'throttle_attempts' => Env::get('AUTH_2FA_THROTTLE_ATTEMPTS', 30),

    'resend_throttle_decay' => Env::get('AUTH_2FA_THROTTLE_DECAY', 15),
    'resend_throttle_attempts' => Env::get('AUTH_2FA_THROTTLE_ATTEMPTS', 30),

    'phone_code_validity_in_minutes' => Env::get('AUTH_2FA_PHONE_CODE_VALIDITY_TIME', 5),
    'remember_hours' => Env::get('AUTH_2FA_REMEMBER_IN_HOURS', 48),
];
