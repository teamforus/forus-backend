<?php

return [
    'connect_timeout' => env('ICONNECT_API_CONNECTION_TIMEOUT', 10),
    'cache_time' => env('ICONNECT_API_CACHE_TIME', 60),

    "cert_path" => env('ICONNECT_API_CRT_PATH', storage_path('/app/certificates/client-crt.pem')),
    "cert_pass" => env('ICONNECT_API_CRT_PASS', ''),

    "key_path" => env('ICONNECT_API_KEY_PATH', storage_path('/app/certificates/client-key.pem')),
    "key_pass" => env('ICONNECT_API_KEY_PASS', ''),

    'cert_trust_pass' => env('ICONNECT_API_CRT_TRUST_PATH', storage_path('/app/certificates/cliq-apitest.locgov.nl_rsa.pem')),

    "target_binding" => env('ICONNECT_API_TARGET_BINDING', 'BurgerlijkeStand'),
    "header_key" => env('ICONNECT_API_HEADER_KEY')
];
