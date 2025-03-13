<?php

namespace App\Services\AuthService;

use App\Models\Identity;
use App\Models\IdentityProxy;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Arr;

class ServiceIdentityProvider implements UserProvider
{
    /**
     * Retrieve a user by the given credentials.
     *
     * @param array $credentials
     * @return Identity|null
     */
    public function retrieveByCredentials(array $credentials): Identity|null
    {
        if (empty($accessToken = Arr::get($credentials, 'bearer_token'))) {
            return null;
        }

        if (!$identityProxy = IdentityProxy::findByAccessToken($accessToken)) {
            return null;
        }

        return $identityProxy->isActive() ? $identityProxy->identity : null;
    }

    /**
     * @param $identifier
     * @return Identity|null
     */
    public function retrieveById($identifier): ?Identity
    {
        return Identity::whereAddress($identifier)->first();
    }

    /**
     * @param $identifier
     * @param $token
     * @return void
     */
    public function retrieveByToken($identifier, $token): void
    {
    }

    /**
     * @param Authenticatable $user
     * @param $token
     * @return void
     */
    public function updateRememberToken(Authenticatable $user, $token): void
    {
    }

    /**
     * @param Authenticatable|Identity $user
     * @param array $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable|Identity $user, array $credentials): bool
    {
        return $user->address === $this->retrieveByCredentials($credentials)->address;
    }

    /**
     * @param Authenticatable $user
     * @param array $credentials
     * @param bool $force
     * @return void
     */
    public function rehashPasswordIfRequired(
        Authenticatable $user,
        array $credentials,
        bool $force = false
    ): void {
    }
}
