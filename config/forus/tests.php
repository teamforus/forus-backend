<?php

return [
    'identity_domain' => env('TESTS_IDENTITY_DOMAIN', 'example.com'),

    'dusk_selector' => env('DUSK_SELECTOR', 'data-dusk'),
    'dusk_wait_for_time' => env('DUSK_WAIT_FOR_TIME', 20),
];
