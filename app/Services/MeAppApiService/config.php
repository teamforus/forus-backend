<?php

use Illuminate\Support\Env;

return [
    'requester' => [
        'namespace' => Env::get('ME_APP_REQUESTER_API_NAMESPACE', 'api/mobile/requester'),
        'deep_link_url' => Env::get('ME_APP_REQUESTER_URL', 'meapp-requester://'),
    ],
    'provider' => [
        'namespace' => Env::get('ME_APP_PROVIDER_API_NAMESPACE', 'api/mobile/provider'),
        'deep_link_url' => Env::get('ME_APP_PROVIDER_URL', 'meapp-provider://'),
    ],
];
