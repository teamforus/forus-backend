<?php

return [
    // primary email for the identity to whom the organizations and funds are attached
    'primary_email' => 'example@example.com',

    // count of providers to be generated
    'providers_count' => 5,
    'validators_count' => 2,
    'provider_offices_count' => 2,
    'provider_products_count' => 4,
    'vouchers_per_fund_count' => 5,

    'prevalidation_bsn' => false,

    // amount in generated vouchers
    'voucher_amount' => 600,

    'voucher_transaction_min' => 5,
    'voucher_transaction_max' => 50,

    'fund_requests_count' => 10,
    'fund_requests_files_count' => 2,
    'fund_request_email_pattern' => '%s@example.com',
    'organization_email_pattern' => '%s@example.com',

    // default bunq key
    'bunq_key' => '',

    // criteria for generated funds
    'fund_criteria' => [[
        'record_type_key' => 'children_nth',
        'operator' => '>',
        'value' => 2,
    ], [
        'record_type_key' => 'net_worth',
        'operator' => '<',
        'value' => 1000,
    ], [
        'record_type_key' => 'gender',
        'operator' => '=',
        'value' => 'vrouwelijk',
    ]],

    // default digid config
    'digid_enabled' => false,
    'digid_app_id' => null,
    'digid_shared_secret' => null,
    'digid_a_select_server' => null,
    'digid_trusted_cert' => 'disable',

    // default implementation frontend urls
    'url_webshop' => 'http://localhost:5500/#!/',
    'url_sponsor' => 'http://localhost:3500/#!/',
    'url_provider' => 'http://localhost:4000/#!/',
    'url_validator' => 'http://localhost:4500/#!/',
    'url_app' => 'http://localhost:9000/#!/',

    'backoffice_url' => null,
    'backoffice_server_key' => '',
    'backoffice_server_cert' => '',
    'backoffice_client_cert' => '',
    'backoffice_client_cert_key' => '',
    'backoffice_fallback' => true,
    'backoffice_enabled' => false,

    'digid_cgi_tls_key' => '',
    'digid_cgi_tls_cert' => '',

    'iconnect_url' => '',
    'iconnect_oin' => '',
    'iconnect_binding' => '',

    'iconnect_env' => '',
    'iconnect_cert' => '',
    'iconnect_cert_pass' => '',
    'iconnect_key' => '',
    'iconnect_key_pass' => '',
    'iconnect_cert_trust' => '',

    'person_bsn_api_record_types' => [[
        'person_bsn_api_field' => 'naam.voornamen',
        'record_type_key' => 'given_name',
    ], [
        'person_bsn_api_field' => 'naam.geslachtsnaam',
        'record_type_key' => 'family_name',
    ], [
        'person_bsn_api_field' => 'geboorte.datum.datum',
        'record_type_key' => 'birth_date',
    ], [
        'person_bsn_api_field' => 'verblijfplaats.straat',
        'record_type_key' => 'street',
    ], [
        'person_bsn_api_field' => 'verblijfplaats.huisnummer',
        'record_type_key' => 'house_number',
    ], [
        'person_bsn_api_field' => 'verblijfplaats.huisnummertoevoeging',
        'record_type_key' => 'house_number_addition',
    ], [
        'person_bsn_api_field' => 'verblijfplaats.postcode',
        'record_type_key' => 'postal_code',
    ]],
];
