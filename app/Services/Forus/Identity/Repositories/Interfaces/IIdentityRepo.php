<?php
namespace App\Services\Forus\Identity\Repositories\Interfaces;

use App\Services\Forus\Identity\Models\IdentityEmail;

interface IIdentityRepo {
    /**
     * Make new identity
     * @param array $records
     * @return string
     */
    public function make(
        array $records = []
    ) : string;

    /**
     * Make new identity by email
     * @param string $primaryEmail
     * @param array $records
     * @return mixed
     * @throws \Exception
     */
    public function makeByEmail(
        string $primaryEmail,
        array $records = []
    );
    /**
     * @param $email
     * @return mixed|null
     * @throws \Exception
     */
    public function getOrMakeByEmail($email);

    /**
     * Create new proxy for given identity
     * @param string $identity
     * @return array
     */
    public function makeIdentityPoxy($identity);

    /**
     * @param $proxyIdentityId
     * @throws \Exception
     */
    public function getProxyAccessToken($proxyIdentityId);

    /**
     * Get proxy identity by access token
     * @param string|null $access_token
     * @return mixed|void
     */
    public function proxyIdByAccessToken(string $access_token = null);

    /**
     * Get proxy identity by access token
     * @param mixed $proxyIdentityId
     * @return string
     */
    public function identityAddressByProxyId($proxyIdentityId = null);

    /**
     * Get proxy identity state by id
     * @param mixed $proxyIdentityId
     * @return mixed
     */
    public function proxyStateById($proxyIdentityId = null);

    /**
     * Destroy proxy identity by id
     *
     * @param $proxyIdentityId
     * @param bool $terminatedByIdentity
     * @return mixed
     */
    public function destroyProxyIdentity(
        $proxyIdentityId,
        $terminatedByIdentity = false
    );

    /**
     * Create new proxy of type
     *
     * @param string $type
     * @param string|null $identityAddress
     * @param string $state
     * @return array
     * @throws \Exception
     */
    public function makeProxy(
        string $type,
        string $identityAddress = null,
        string $state = 'pending'
    );

    /**
     * Make code authorization proxy identity
     * @return array
     */
    public function makeAuthorizationCodeProxy();

    /**
     * Make token authorization proxy identity
     * @return array
     */
    public function makeAuthorizationTokenProxy();

    /**
     * Make token authorization proxy identity
     *
     * @return array
     * @throws \Exception
     */
    public function makeAuthorizationShortTokenProxy();

    /**
     * Make email token authorization proxy identity
     * @param string $identityAddress
     * @return array
     */
    function makeAuthorizationEmailProxy(string $identityAddress);

    /**
     * Authorize proxy identity by code
     *
     * @param string $identityAddress
     * @param string $code
     * @return bool
     */
    public function activateAuthorizationCodeProxy(
        string $identityAddress,
        string $code
    );

    /**
     * Authorize proxy identity by token
     *
     * @param string $identityAddress
     * @param string $token
     * @return bool|mixed
     */
    public function activateAuthorizationTokenProxy(
        string $identityAddress,
        string $token
    );

    /**
     * Authorize proxy identity by token
     *
     * @param string $identityAddress
     * @param string $token
     * @return bool|mixed
     */
    public function activateAuthorizationShortTokenProxy(
        string $identityAddress,
        string $token
    );

    /**
     * Authorize proxy identity by token
     *
     * @param string $token
     * @return bool|mixed
     */
    public function exchangeAuthorizationShortTokenProxy(string $token);

    /**
     * Authorize proxy identity by email token
     * @param string $token
     * @return string
     */
    public function activateAuthorizationEmailProxy(string $token);

    /**
     * Authorize proxy identity by email token
     *
     * @param string $token
     * @return string
     */
    public function exchangeEmailConfirmationToken(string $token);

    /**
     * Authorize proxy identity by email token
     *
     * @param string $token
     * @return string
     */
    public function exchangeQrCodeToken(string $token);

    /**
     * @param string $identity_address
     * @return string|null
     */
    public function getPrimaryEmail(string $identity_address): ?string;

    /**
     * @param string $primary_email
     * @return string|null
     */
    public function getAddress(string $primary_email): ?string;

    /**
     * @param string $identity_address
     * @param string $primary_email
     * @param bool $verified
     * @param bool $primary
     * @return IdentityEmail
     */
    public function addIdentityEmail(
        string $identity_address,
        string $primary_email,
        bool $verified = false,
        bool $primary = false
    ): IdentityEmail;

    /**
     * @param string $email
     * @return bool
     */
    public function isEmailAvailable(
        string $email
    ): bool;

    /**
     * Search identity addresses by email substring
     * @param string $search
     * @return array
     */
    public function identityAddressesByEmailSearch(
        string $search
    ): array;
}
