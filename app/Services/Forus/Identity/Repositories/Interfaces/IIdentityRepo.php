<?php
namespace App\Services\Forus\Identity\Repositories\Interfaces;

use App\Services\Forus\Identity\Models\IdentityProxy;

interface IIdentityRepo {
    /**
     * Make new identity
     * @param string $pinCode
     * @param array $records
     * @return mixed
     */
    public function make(
        string $pinCode ,
        array $records = []
    );

    /**
     * Make new identity by email
     * @param string $primaryEmail
     * @param array $records
     * @param int $pinCode
     * @return mixed
     * @throws \Exception
     */
    public function makeByEmail(
        string $primaryEmail,
        array $records = [],
        $pinCode = 1111
    );

    /**
     * Create new proxy for given identity
     * @param string $identity
     * @return array
     */
    public function makeIdentityPoxy(
        $identity
    );

    /**
     * @param $proxyIdentityId
     * @throws \Exception
     */
    public function getProxyAccessToken(
        $proxyIdentityId
    );

    /**
     * Get proxy identity by access token
     * @param string $access_token
     * @return mixed|void
     */
    public function proxyIdByAccessToken(
        string $access_token = null
    );

    /**
     * Get proxy identity by access token
     * @param mixed $proxyIdentityId
     * @return string
     */
    public function identityAddressByProxyId(
        $proxyIdentityId = null
    );

    /**
     * Get proxy identity state by id
     * @param mixed $proxyIdentityId
     * @return mixed
     */
    public function proxyStateById(
        $proxyIdentityId = null
    );

    /**
     * Destroy proxy identity by id
     * @param mixed $proxyIdentityId
     * @return mixed|void
     * @throws \Exception
     */
    public function destroyProxyIdentity(
        $proxyIdentityId
    );

    /**
     * @param $proxyIdentityId
     * @return bool
     * @throws \Exception
     */
    public function hasPinCode(
        $proxyIdentityId
    );

    /**
     * @param mixed $proxyIdentityId
     * @param string $pinCode
     * @return bool
     * @throws \Exception
     */
    public function cmpPinCode(
        $proxyIdentityId,
        $pinCode
    );

    /**
     * @param $proxyIdentityId
     * @param string $pinCode
     * @param string $oldPinCode
     * @return bool
     * @throws \Exception
     */
    public function updatePinCode(
        $proxyIdentityId,
        $pinCode,
        $oldPinCode = null
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
    function makeAuthorizationEmailProxy(
        string $identityAddress
    );

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
    public function exchangeAuthorizationShortTokenProxy(
        string $token
    );

    /**
     * Authorize proxy identity by email token
     * @param string $token
     * @return string
     */
    public function activateAuthorizationEmailProxy(
        string $token
    );

    /**
     * Authorize proxy identity by email token
     *
     * @param string $token
     * @return string
     */
    public function exchangeEmailConfirmationToken(
        string $token
    );

    /**
     * Authorize proxy identity by email token
     *
     * @param string $token
     * @return string
     */
    public function exchangeQrCodeToken(
        string $token
    );
}
