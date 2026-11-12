<?php

use Illuminate\Support\Env;

return [
    'enabled' => (bool) Env::get('DCP_ENABLED', false),
    'disabled_commands' => [
        'dcp:send-orders' => (bool) Env::get('DCP_DISABLE_SEND_ORDERS', false),
        'dcp:update-status' => (bool) Env::get('DCP_DISABLE_UPDATE_STATUS', false),
    ],
    'environments' => [
        'sandbox' => Env::get('DCP_SANDBOX_URL', 'https://api-acc.dcp.nl'),
        'production' => Env::get('DCP_PRODUCTION_URL', 'https://api.dcp.nl'),
    ],
    'orderable_types' => ['voucher_pass'],
    'connect_timeout' => 10,
    'timeout' => 30,
    'processing_chunk_size' => 100,
    'sending_claim_timeout' => 300,
    'send_schedule' => '* * * * *',
    'status_schedule' => '0 0 * * *',
];
