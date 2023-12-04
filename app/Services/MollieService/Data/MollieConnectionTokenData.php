<?php

namespace App\Services\MollieService\Data;

use Carbon\Carbon;
use App\Services\MollieService\Interfaces\MollieToken;
use App\Services\MollieService\Models\MollieConnection;
use Illuminate\Support\Facades\Config;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;

class MollieConnectionTokenData implements MollieToken
{
    /**
     * @param MollieConnection $connection
     */
    public function __construct(protected MollieConnection $connection) {}

    /**
     * @return string|null
     */
    public function getAccessToken(): ?string
    {
        return $this->connection->active_token?->access_token;
    }

    /**
     * @return string|null
     */
    public function getRefreshToken(): ?string
    {
        return $this->connection->active_token?->remember_token;
    }

    /**
     * @param AccessTokenInterface|AccessToken $token
     * @return void
     */
    public function setAccessToken(AccessTokenInterface|AccessToken $token): void
    {
        $expire_at = Carbon::createFromTimestamp($token->getExpires());

        $this->connection->tokens()->delete();

        $this->connection->tokens()->create([
            'expired_at' => $expire_at->subSeconds(Config::get('mollie.token_expire_offset')),
            'access_token' => $token->getToken(),
            'remember_token' => $token->getRefreshToken(),
        ]);

        $this->connection->refresh();
    }

    /**
     * @return void
     */
    public function deleteToken(): void
    {
        $this->connection->tokens()->delete();
    }

    /**
     * @return bool
     */
    public function isTokenExpired(): bool
    {
        return $this->connection->active_token && $this->connection->active_token?->isExpired();
    }

    /**
     * @return bool
     */
    public function hasToken(): bool
    {
        return (bool) $this->connection?->active_token;
    }
}