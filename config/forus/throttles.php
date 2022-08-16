<?php

return [
    'fund_check' => [
        'attempts' => env('FUND_CHECK_ATTEMPTS', 20),
        'decay' => env('FUND_CHECK_DECAY', 60),
    ],
    'activation_code' => [
        'attempts' => env('ACTIVATION_CODE_ATTEMPTS', 3),
        'decay' => env('ACTIVATION_CODE_DECAY', 180),
    ],
];