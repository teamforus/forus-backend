<?php

return [
    'storage_driver' => env('BUNQ_STORAGE_DRIVER', "private"),
    'storage_path' => 'bunq_context/funds/',
    'skip_iban_numbers' => array_filter(explode(',', env('BUNQ_IBAN_SKIP_LIST', ''))),
];