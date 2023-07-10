<?php

use Illuminate\Support\Env;

return [
    'throttle' => [
        'throttle_decay' => Env::get('BI_CONNECTION_THROTTLE_DECAY', 10),
        'throttle_attempts' => Env::get('BI_CONNECTION_THROTTLE_ATTEMPTS', 10),
    ],
];
