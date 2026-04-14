<?php

return [
    'identity_domain' => env('TESTS_IDENTITY_DOMAIN', 'example.com'),

    'dusk_selector' => env('DUSK_SELECTOR', 'data-dusk'),
    'dusk_wait_for_time' => env('DUSK_WAIT_FOR_TIME', 20),
    'dusk_type_slowly' => env('DUSK_TYPE_SLOWLY', true),
    'dusk_type_slowly_pause' => env('DUSK_TYPE_SLOWLY_PAUSE', 10),
    'dusk_github_action' => env('DUSK_GITHUB_ACTION', false),
];
