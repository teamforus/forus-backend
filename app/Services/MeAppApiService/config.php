<?php

use App\Models\Implementation;
use Illuminate\Support\Env;

$attemptsMultiplier = (int) Env::get('ME_APP_THROTTLE_ATTEMPTS_MULTIPLIER', 1);

return [
    'throttles' => [
        'attempts_multiplier' => $attemptsMultiplier,
        'auth_email' => [
            'attempts' => (int) Env::get('ME_APP_THROTTLE_AUTH_EMAIL_ATTEMPTS', 10) * $attemptsMultiplier,
            'decay_minutes' => (int) Env::get('ME_APP_THROTTLE_AUTH_EMAIL_DECAY_MINUTES', 10),
        ],
        'auth_create' => [
            'attempts' => (int) Env::get('ME_APP_THROTTLE_AUTH_CREATE_ATTEMPTS', 10) * $attemptsMultiplier,
            'decay_minutes' => (int) Env::get('ME_APP_THROTTLE_AUTH_CREATE_DECAY_MINUTES', 1),
        ],
        'auth_approve' => [
            'attempts' => (int) Env::get('ME_APP_THROTTLE_AUTH_APPROVE_ATTEMPTS', 10) * $attemptsMultiplier,
            'decay_minutes' => (int) Env::get('ME_APP_THROTTLE_AUTH_APPROVE_DECAY_MINUTES', 1),
        ],
        'auth_exchange' => [
            'attempts' => (int) Env::get('ME_APP_THROTTLE_AUTH_EXCHANGE_ATTEMPTS', 60) * $attemptsMultiplier,
            'decay_minutes' => (int) Env::get('ME_APP_THROTTLE_AUTH_EXCHANGE_DECAY_MINUTES', 1),
        ],
        'auth_poll' => [
            'attempts' => (int) Env::get('ME_APP_THROTTLE_AUTH_POLL_ATTEMPTS', 120) * $attemptsMultiplier,
            'decay_minutes' => (int) Env::get('ME_APP_THROTTLE_AUTH_POLL_DECAY_MINUTES', 1),
        ],
        'auth_redirect' => [
            'attempts' => (int) Env::get('ME_APP_THROTTLE_AUTH_REDIRECT_ATTEMPTS', 60) * $attemptsMultiplier,
            'decay_minutes' => (int) Env::get('ME_APP_THROTTLE_AUTH_REDIRECT_DECAY_MINUTES', 1),
        ],
        'identity_read' => [
            'attempts' => (int) Env::get('ME_APP_THROTTLE_IDENTITY_READ_ATTEMPTS', 120) * $attemptsMultiplier,
            'decay_minutes' => (int) Env::get('ME_APP_THROTTLE_IDENTITY_READ_DECAY_MINUTES', 1),
        ],
    ],

    'requester' => [
        'client_types' => array_map(
            'trim',
            explode(',', Env::get('ME_APP_REQUESTER_CLIENT_TYPES', implode(',', [
                Implementation::ME_APP_REQUESTER_ANDROID, Implementation::ME_APP_REQUESTER_IOS,
            ]))),
        ),

        'client_keys' => array_map(
            'trim',
            explode(',', Env::get('ME_APP_REQUESTER_CLIENT_KEYS', Implementation::KEY_GENERAL)),
        ),

        'namespace' => Env::get('ME_APP_REQUESTER_API_NAMESPACE', 'api/mobile/requester'),
        'deep_link_url' => Env::get('ME_APP_REQUESTER_URL', 'meapp-requester://'),
    ],
    'provider' => [
        'client_types' => array_map(
            'trim',
            explode(',', Env::get('ME_APP_PROVIDER_CLIENT_TYPES', implode(',', [
                Implementation::ME_APP_PROVIDER_ANDROID, Implementation::ME_APP_PROVIDER_IOS,
            ]))),
        ),

        'client_keys' => array_map(
            'trim',
            explode(',', Env::get('ME_APP_PROVIDER_CLIENT_KEYS', Implementation::KEY_GENERAL)),
        ),

        'namespace' => Env::get('ME_APP_PROVIDER_API_NAMESPACE', 'api/mobile/provider'),
        'deep_link_url' => Env::get('ME_APP_PROVIDER_URL', 'meapp-provider://'),
    ],
];
