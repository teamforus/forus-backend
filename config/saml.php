<?php

return [
    "useRoutes" => true,
    "routesPrefix" => "/saml2",
    "routesMiddleware" => [],
    "retrieveParametersFromServer" => false,
    "loginRoute" => null,
    "logoutRoute" => null,
    "errorRoute" => null,

    'strict' => true,
    'debug' => false,
    'baseurl' => null,

    "proxyVars" => false,

    "security" => [
        "nameIdEncrypted" => false,
        "authnRequestsSigned" => true,
        "logoutRequestSigned" => false,
        "logoutResponseSigned" => false,
        "signMetadata" => true,
        "wantMessagesSigned" => false,
        "wantAssertionsSigned" => false,
        "wantNameIdEncrypted" => false,
        'requestedAuthnContext' => [
            'urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport',
        ],
        'requestedAuthnContextComparison' => 'minimum',
    ],

    'sp' => [],
    'idp' => [],
];
