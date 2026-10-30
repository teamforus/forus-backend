<?php

use App\Models\Implementation;
use Illuminate\Support\Env;

return [
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
