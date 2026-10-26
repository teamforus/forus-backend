<?php

return [
    'strict' => true,
    'debug' => false,
    'baseurl' => null,

    'proxyVars' => false,

    'security' => [
        'nameIdEncrypted' => false,
        'authnRequestsSigned' => true,
        'logoutRequestSigned' => false,
        'logoutResponseSigned' => false,
        'signMetadata' => true,
        'wantMessagesSigned' => false,
        'wantAssertionsSigned' => true,
        'wantNameIdEncrypted' => false,
        'requestedAuthnContext' => [],
        'requestedAuthnContextComparison' => 'minimum',
    ],

    'sp' => [
        'entityId' => env('TVS_LC_ENTITY_ID'),

        'assertionConsumerService' => [
            'url' => env('TVS_ACS_URL'),
            'index' => 0,
        ],

        'singleLogoutService' => [
            'url' => env('TVS_SLO_URL', 'https://rd2.toegang.overheid.nl/kvs/rd/request_logout'),
        ],

        'x509cert' => '',
        'privateKey' => '',
    ],

    'idp' => [
        'certData' => '',

        'entityId' => env('TVS_RD_ENTITY_ID'),

        'singleSignOnService' => [
            'url' => env('TVS_RD_SSO_URL', 'https://rd2.toegang.overheid.nl/kvs/rd/request_authentication'),
        ],

        'singleLogoutService' => [
            'url' => env('TVS_RD_SLO_URL', 'https://rd2.toegang.overheid.nl/kvs/rd/request_logout'),
        ],

        'artifactResolutionService' => [
            'url' => env('TVS_RD_ARS_URL', 'https://artifact-rd2.toegang.overheid.nl/kvs/rd/resolve_artifact'),
        ],

        'x509cert' => '',
    ],
];
