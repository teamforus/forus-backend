<?php

return [
    // primary email for the identity to whom the organizations and funds are attached
    'default_email'             => env('DB_SEED_BASE_EMAIL', 'example@example.com'),

    // count of providers to be generated
    'providers_count'           => env('DB_SEED_PROVIDERS', 20),

    // amount in generated vouchers
    'voucher_amount'            => env('DB_SEED_VOUCHER_AMOUNT', 600),

    'voucher_transaction_min'   => env('DB_SEED_VOUCHER_TRANS_MIN', 5),
    'voucher_transaction_max'   => env('DB_SEED_VOUCHER_TRANS_MAX', 50),

    // default bunq key
    'bunq_key'              => env('DB_SEED_BUNQ_KEY', ''),

    // criteria for generated funds
    'funds_criteria'        => [[
        'record_type_key'   => 'children_nth',
        'operator'          => '>',
        'value'             => 2,
    ], [
        'record_type_key'   => 'net_worth',
        'operator'          => '<',
        'value'             => 1000,
    ], [
        'record_type_key'   => 'gender',
        'operator'          => '=',
        'value'             => 'Female',
    ]],

    // default digid config
    'digid_enabled'         => !empty(env('DB_SEED_DIGID_SHARED_SECRET', null)),
    'digid_app_id'          => env('DB_SEED_DIGID_APP_ID', null),
    'digid_shared_secret'   => env('DB_SEED_DIGID_SHARED_SECRET', null),
    'digid_a_select_server' => env('DB_SEED_DIGID_A_SELECT_SERVER', null),


    // default implementation frontend urls
    'url_webshop' => env(
        'DB_SEED_URL_WEBSHOP',
        "https://dev.:key.forus.io/#!/"
    ),
    'url_sponsor' => env(
        'DB_SEED_URL_SPONSOR',
        "https://dev.:key.forus.io/sponsor/#!/"
    ),
    'url_provider' => env(
        'DB_SEED_URL_PROVIDER',
        "https://dev.:key.forus.io/provider/#!/"
    ),
    'url_validator' => env(
        'DB_SEED_URL_VALIDATOR',
        "https://dev.:key.forus.io/validator/#!/"
    ),
    'url_app' => env(
        'DB_SEED_URL_APP',
        "https://dev.:key.forus.io/me/#!/"
    ),
];