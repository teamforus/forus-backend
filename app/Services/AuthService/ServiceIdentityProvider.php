<?php

namespace App\Services\AuthService;

use App\Services\AuthService\Models\Identity;
use App\Services\Forus\Identity\Repositories\IdentityRepo;
use App\Services\Forus\Identity\Repositories\Interfaces\IIdentityRepo;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

class ServiceIdentityProvider implements UserProvider
{
    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        if (empty($credentials) ||
            !array_key_exists('bearer_token', $credentials) ||
            empty($credentials['bearer_token']) ||
            $credentials['bearer_token'] == 'null'
        ) {
            return null;
        }

        $bearerToken = $credentials['bearer_token'];
        $identityService = resolve('forus.services.identity');

        if (!$proxyIdentityId = $identityService->proxyIdByAccessToken($bearerToken)) {
            return null;
        }

        $proxyIdentityState = $identityService->proxyStateById($proxyIdentityId);
        $identityAddress = $identityService->identityAddressByProxyId($proxyIdentityId);

        return new Identity($identityAddress, $proxyIdentityId, $proxyIdentityState);
    }

    public function retrieveById($identifier)
    {
        // TODO: Implement retrieveById() method.
    }

    public function retrieveByToken($identifier, $token)
    {
        // TODO: Implement retrieveByToken() method.
    }

    public function updateRememberToken(Authenticatable $user, $token)
    {
        // TODO: Implement updateRememberToken() method.
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        // TODO: Implement validateCredentials() method.
    }
}