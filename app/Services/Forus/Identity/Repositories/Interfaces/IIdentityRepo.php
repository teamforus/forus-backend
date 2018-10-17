<?php
namespace App\Services\Forus\Identity\Repositories\Interfaces;

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
     * Create new proxy for given identity
     * @param $identityAddress string
     * @return array
     */
    public function makeIdentityPoxy(
        $identityAddress
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
     * Make email token authorization proxy identity
     * @param string $identityAddress
     * @return array
     */
    function makeAuthorizationEmailProxy(
        string $identityAddress
    );

    /**
     * Authorize proxy identity by code
     * @param $identityAddress string
     * @param string $code
     * @return mixed
     */
    public function activateAuthorizationCodeProxy(
        string $identityAddress,
        string $code
    );

    /**
     * Authorize proxy identity by token
     * @param $identityAddress string
     * @param string $token
     * @return mixed
     */
    public function activateAuthorizationTokenProxy(
        string $identityAddress,
        string $token
    );

    /**
     * Authorize proxy identity by email token
     * @param string $email_token
     * @return string
     */
    public function activateAuthorizationEmailProxy(
        string $email_token
    );
}
