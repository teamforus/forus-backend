<?php

return [
    'test_mode' => env('MOLLIE_TEST_MODE', true),
    'client_id' => env('MOLLIE_CLIENT_ID', ''),
    'client_secret' => env('MOLLIE_CLIENT_SECRET', ''),
    'redirect_url' => env('MOLLIE_REDIRECT_URI', '/mollie/callback'),
    'webhook_url' => env('MOLLIE_WEBHOOK_URI', '/mollie/webhooks'),

    'base_access_token' => env('MOLLIE_ACCESS_TOKEN', ''),
    'token_expire_offset' => env('MOLLIE_TOKEN_EXPIRE_OFFSET', 60 * 5),

    'test_data' => [
        'connection' => [
            'organization' => [
                'id' => 'organization_1234',
                'name' => 'Test organization',
                'city' => 'Groningen',
                'email' => 'example@example.com',
                'street' => 'street',
                'country' => 'Netherlands',
                'postcode' => '542033CL',
                'last_name' => 'Doe',
                'first_name' => 'John',
                'vat_number' => '00000',
                'onboarding_state' => 'completed',
                'registration_number' => '0000',
            ],

            'profile' => [
                'id' => 'profile_1234',
                'name' => 'John Doe',
                'email' => 'example@example.com',
                'phone' => '+31123456789',
                'status' => \App\Services\MollieService\Models\MollieConnectionProfile::STATE_ACTIVE,
                'website' => 'https://forus.io',
                'created_at' => null,
            ],

            'payment_method' => [
                'id' => 'ideal',
                'description' => 'iDEAL',
                'status' => 'activated'
            ],

            'organization_id' => 1,
        ],

        'payment' => [
            'id' => 'payment_1234',
            'amount' => 10,
            'status' => \App\Models\ReservationExtraPayment::STATE_PAID,
            'method' => 'ideal',
            'paid_at' => null,
            'currency' => 'EUR',
            'created_at' => null,
            'expires_at' => null,
            'expired_at' => null,
            'profile_id' => 'profile_1234',
            'canceled_at' => null,
            'description' => 'Payment 1234',
            'amount_refunded' => 0,
            'amount_captured' => null,
            'amount_remaining' => 10,
            'checkout_url' => '',
        ],

        'refund' => null,

        'refund_sample' => [
            'id' => 'refund_1234',
            'payment_id' => 'payment_1234',
            'amount' => 10,
            'currency' => 'EUR',
            'status' => \App\Models\ReservationExtraPaymentRefund::STATE_REFUNDED,
            'description' => 'Refund payment',
            'created_at' => null,
        ],
    ],
];
