<?php

namespace App\Services\MollieService\Data;

use App\Services\MollieService\Interfaces\MollieToken;
use Illuminate\Support\Facades\Config;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;

class ForusTokenData implements MollieToken {
    /**
     * @return string|null
     */
    public function getAccessToken(): ?string
    {
        return Config::get('mollie.base_access_token');
    }

    /**
     * @return string|null
     */
    public function getRefreshToken(): ?string
    {
        return null;
    }

    /**
     * @param AccessTokenInterface|AccessToken $token
     * @return void
     */
    public function setAccessToken(AccessTokenInterface|AccessToken $token): void {}

    /**
     * @return void
     */
    public function deleteToken(): void {}

    /**
     * @return bool
     */
    public function isTokenExpired(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function hasToken(): bool
    {
        return (bool) $this->getAccessToken();
    }
}