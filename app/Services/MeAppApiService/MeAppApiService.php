<?php

namespace App\Services\MeAppApiService;

use App\Models\Implementation;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use LogicException;

class MeAppApiService
{
    /**
     * @param string|null $clientType
     * @return string|null
     */
    public function appKeyForClientType(?string $clientType): ?string
    {
        return match ($clientType) {
            Implementation::ME_APP_REQUESTER_ANDROID, Implementation::ME_APP_REQUESTER_IOS => 'requester',
            Implementation::ME_APP_PROVIDER_ANDROID, Implementation::ME_APP_PROVIDER_IOS => 'provider',
            default => null,
        };
    }

    /**
     * @param string|null $clientType
     * @throws LogicException
     * @return string
     */
    public function deepLinkBaseUrl(?string $clientType): string
    {
        $appKey = $this->appKeyForClientType($clientType);

        if (!$appKey) {
            return (string) Config::get('forus.front_ends.app-me_app');
        }

        $url = match ($appKey) {
            'requester' => Config::get('me-app-api.requester.deep_link_url'),
            'provider' => Config::get('me-app-api.provider.deep_link_url'),
        };

        if (!is_string($url) || $url === '') {
            throw new LogicException('The mobile app deep-link URL is not configured.');
        }

        return str_ends_with($url, '/') ? $url : $url . '/';
    }

    /**
     * @param string|null $clientType
     * @param bool $confirmation
     * @param array $parameters
     * @return string|null
     */
    public function emailRedirectUrl(?string $clientType, bool $confirmation, array $parameters): ?string
    {
        $appKey = $this->appKeyForClientType($clientType);

        if (!$appKey) {
            return null;
        }

        $action = $confirmation ? 'confirmation' : 'email';

        return URL::route("mobile.$appKey.v1.identity.proxy.$action.redirect", $parameters);
    }

    /**
     * @param string|null $clientType
     * @param string|null $implementationKey
     * @return array
     */
    public function authPageContext(?string $clientType, ?string $implementationKey): array
    {
        return $this->appKeyForClientType($clientType) ? compact('clientType', 'implementationKey') : [];
    }
}
