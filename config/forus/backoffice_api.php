<?php

return [
    'connect_timeout' => env('BACKOFFICE_CONNECTION_TIMEOUT', 10),

    "cert_path" => env('BACKOFFICE_CRT_PATH', storage_path('/app/backoffice/client-crt.pem')),
    "cert_pass" => env('BACKOFFICE_CRT_PASS', 'password'),

    "key_path" => env('BACKOFFICE_KEY_PATH', storage_path('/app/backoffice/client-key.pem')),
    "key_pass" => env('BACKOFFICE_KEY_PASS', 'password'),
];