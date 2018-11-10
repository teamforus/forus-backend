<?php

namespace App\Services\AuthService;

use App\Services\AuthService\Models\Identity;
use App\Services\Forus\Identity\Repositories\IdentityRepo;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

class ServiceIdentityProvider implements UserProvider
{
    private $identityRepo;

    public function __construct(IdentityRepo $identityRepo)
    {
        $this->identityRepo = $identityRepo;
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        if (empty($credentials) || (count($credentials) !== 1) ||
            !array_key_exists('bearer_token', $credentials)) {
            return null;
        }

        $bearerToken = $credentials['bearer_token'];

        $identityService = app()->make('forus.services.identity');

        $proxyIdentityId = $identityService->proxyIdByAccessToken($bearerToken);
        $proxyIdentityState = $identityService->proxyStateById($proxyIdentityId);
        $identityAddress = $identityService->identityAddressByProxyId($proxyIdentityId);

        return new Identity($identityAddress, $proxyIdentityId, $proxyIdentityState);
        // TODO: Implement retrieveByCredentials() method.
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