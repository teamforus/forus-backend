<?php

namespace App\Services\MollieService\Interfaces;

use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;

interface MollieToken
{
    public function getAccessToken(): ?string;
    public function getRefreshToken(): ?string;
    public function setAccessToken(AccessTokenInterface|AccessToken $token): void;
    public function deleteToken(): void;
    public function isTokenExpired(): bool;
    public function hasToken(): bool;
}