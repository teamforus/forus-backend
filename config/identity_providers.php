<?php

$appUrl = rtrim(env('APP_URL', 'http://localhost'), '/');
$redirectUrl = $appUrl . '/api/v1/platform/identity-providers/entra/oidc/callback';
$adminConsentRedirectUrl = $appUrl . '/api/v1/platform/identity-providers/entra/admin-consent/callback';

return [
    'enabled' => env('IDENTITY_PROVIDER_ENABLED', false),
    'log_channel' => env('IDENTITY_PROVIDER_LOG_CHANNEL', 'openid'),
    'oidc_session_minutes' => env('IDENTITY_PROVIDER_OIDC_SESSION_MINUTES', 10),
    'exchange_seconds' => env('IDENTITY_PROVIDER_EXCHANGE_SECONDS', 60),
    'admin_session_minutes' => env('IDENTITY_PROVIDER_ADMIN_SESSION_MINUTES', 30),
    'session_retention_days' => env('IDENTITY_PROVIDER_SESSION_RETENTION_DAYS', 180),
    'throttle' => [
        'authorization_requests_per_minute' => env('IDENTITY_PROVIDER_THROTTLE_AUTHORIZATION_REQUESTS_PER_MINUTE', 20),
        'callbacks_and_exchanges_per_minute' => env('IDENTITY_PROVIDER_THROTTLE_CALLBACKS_AND_EXCHANGES_PER_MINUTE', 120),
    ],
    'entra' => [
        'client_id' => env('ENTRA_CLIENT_ID'),
        'client_secret' => env('ENTRA_CLIENT_SECRET'),
        'redirect_url' => env('ENTRA_REDIRECT_URL') ?: $redirectUrl,
        'admin_consent_redirect_url' => env('ENTRA_ADMIN_CONSENT_REDIRECT_URL') ?: $adminConsentRedirectUrl,
        'scopes' => ['openid', 'profile'],
        'admin_role_ids' => [
            '62e90394-69f5-4237-9190-012177145e10',
            'e8611ab8-c189-46e8-94e1-60213ab1f814',
            '158c047a-c907-4556-b7ef-446551a6b5f7',
            '9b895d92-2cd3-44c7-9d02-a6ac2d5ea5c3',
        ],
        'code_challenge_method' => 'S256',
        'id_token_signed_response_alg' => 'RS256',
        'token_endpoint_auth_method' => 'client_secret_post',
    ],
];
