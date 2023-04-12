<?php

return [
    'psu_ip' => env('BNG_PSU_IP_ADDRESS', 'auto'),
    'auth_redirect_url' => env('BNG_REDIRECT_URL', '/bng'),

    'notify' => [
        'expire_time' => [
            'notification' => [
                'unit' => env('BNG_EXPIRE_NOTIFICATION_UNIT', 'day'),
                'value' => env('BNG_EXPIRE_NOTIFICATION_UNIT_VALUE', 14),
            ],
            'announcement' => [
                'unit' => env('BNG_EXPIRE_ANNOUNCEMENT_UNIT', 'day'),
                'value' => env('BNG_EXPIRE_ANNOUNCEMENT_UNIT_VALUE', 7),
            ],
        ],
    ],
];