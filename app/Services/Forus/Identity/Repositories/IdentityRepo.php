<?php

namespace App\Services\Forus\Identity\Repositories;

use App\Services\Forus\Identity\Models\Identity;
use App\Services\Forus\Identity\Models\IdentityEmail;
use App\Services\Forus\Identity\Models\IdentityProxy;
use App\Services\Forus\Record\Repositories\Interfaces\IRecordRepo;

class IdentityRepo implements Interfaces\IIdentityRepo
{
    protected $model;
    protected $recordRepo;

    /**
     * How many time user have to exchange their exchange_token
     * @var array
     */
    protected $expirationTimes = [
        // 1 minute
        'short_token' => 60,
        // 10 minutes
        'pin_code' => 60 * 10,
        // 60 minutes
        'qr_code' => 60 * 60,
        // 60 minutes
        'email_code' => 60 * 60,
        // 1 month
        'confirmation_code' => 60 * 60 * 24 * 30,
    ];

    /**
     * IdentityRepo constructor.
     * @param Identity $model
     * @param IRecordRepo $recordRepo
     */
    public function __construct(Identity $model, IRecordRepo $recordRepo)
    {
        $this->model = $model;
        $this->recordRepo = $recordRepo;
    }

    /**
     * Make new identity
     * @param array $records
     * @throws \Exception
     * @return string
     */
    public function make(array $records = []): string
    {
        $identity = $this->model->create(app('key_pair_generator')->make());

        $identity->addEmail($records['primary_email'], false, true, true);
        $this->recordRepo->updateRecords($identity->address, $records);

        return $identity->address;
    }

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
    ) {
        $identityAddress = $this->make(array_merge([
            'primary_email' => $primaryEmail
        ], $records));

        $this->recordRepo->categoryCreate($identityAddress, "Relaties");

        return $identityAddress;
    }

    /**
     * @param $email
     * @return mixed|null
     * @throws \Exception
     */
    public function getOrMakeByEmail($email)
    {
        if (!$email || empty($email)) {
            return null;
        }

        $address = $this->recordRepo->identityAddressByEmail($email);
        return $address ?: $this->makeByEmail($email);
    }

    /**
     * Create new proxy for given identity
     *
     * @param string $identity
     * @return array|\Illuminate\Support\Collection
     * @throws \Exception
     */
    public function makeIdentityPoxy($identity)
    {
        return $this->makeProxy('confirmation_code', $identity);
    }

    /**
     * Get access_token by proxy identity id
     * @param $proxyIdentityId
     * @return mixed
     * @throws \Exception
     */
    public function getProxyAccessToken($proxyIdentityId)
    {
        if (!$proxyIdentity = IdentityProxy::find($proxyIdentityId)) {
            throw new \Exception(
                trans('identity.exceptions.unknown_identity')
            );
        }

        return $proxyIdentity['access_token'];
    }

    /**
     * Get proxy identity by access token
     * @param string $access_token
     * @return mixed
     */
    public function proxyIdByAccessToken(
        string $access_token = null
    ) {
        if (!empty($access_token) && $proxyIdentity =
                IdentityProxy::findByAccessToken($access_token)) {
            return $proxyIdentity->id;
        }

        return false;
    }

    /**
     * Get proxy identity by access token
     * @param mixed $proxyIdentityId
     * @return string
     */
    public function identityAddressByProxyId($proxyIdentityId = null)
    {
        $proxyIdentity = IdentityProxy::find($proxyIdentityId);

        if ($proxyIdentity && $proxyIdentity->identity) {
            return $proxyIdentity->identity->address;
        }

        return null;
    }

    /**
     * Get proxy identity state by id
     * @param mixed $proxyIdentityId
     * @return mixed
     */
    public function proxyStateById($proxyIdentityId = null)
    {
        return IdentityProxy::find($proxyIdentityId)->state ?? null;
    }

    /**
     * Destroy proxy identity by id
     *
     * @param $proxyIdentityId
     * @param bool $terminatedByIdentity
     * @return mixed|void
     * @throws \Exception
     */
    public function destroyProxyIdentity(
        $proxyIdentityId,
        $terminatedByIdentity = false
    ) {
        if ($identityProxy = IdentityProxy::find($proxyIdentityId)) {
            $identityProxy->update([
                'state' => $terminatedByIdentity ? 'terminated' : 'destroyed',
            ]);

            $identityProxy->delete();
        }
    }

    /**
     * @param $type
     * @return int|string
     * @throws \Exception
     */
    private function uniqExchangeToken($type)
    {
        do {
            switch ($type) {
                case "qr_code": $token = $this->makeToken(64); break;
                case "pin_code": $token = random_int(111111, 999999); break;
                case "email_code": $token = $this->makeToken(128); break;
                case "short_token":
                case "confirmation_code": $token = $this->makeToken(200); break;
                default: throw new \Exception(trans('identity-proxy.unknown_token_type'));
            }
        } while(IdentityProxy::query()->where([
            'exchange_token' => $token
        ])->count() > 0);

        return $token;
    }


    /**
     * Create new proxy of type
     *
     * @param string $type
     * @param string|null $identityAddress
     * @param string $state
     * @return array|bool
     */
    public function makeProxy(
        string $type,
        string $identityAddress = null,
        string $state = 'pending'
    ) {
        try {
            $exchangeToken = $this->uniqExchangeToken($type);
        } catch (\Throwable $e) {
            logger()->error($e->getMessage());
            abort(400);
        }

        return $this->createProxy(
            $exchangeToken,
            $type,
            $this->expirationTimes[$type],
            $identityAddress,
            $state
        );
    }

    /**
     * Create new proxy
     *
     * @param string $exchange_token
     * @param string $type
     * @param int $expires_in
     * @param string|null $identity_address
     * @param string $state
     * @return array
     */
    private function createProxy(
        string $exchange_token,
        string $type,
        int $expires_in,
        string $identity_address = null,
        string $state = 'pending'
    ) {
        $access_token = $this->makeAccessToken();

        return collect(IdentityProxy::query()->create(compact(
            'identity_address', 'exchange_token', 'type',
            'expires_in', 'state', 'access_token'
        )))->only([
            'identity_address', 'exchange_token', 'type', 'expires_in',
            'state', 'access_token'
        ])->toArray();
    }

    /**
     * Make code authorization proxy identity
     *
     * @return Identity|array|\Illuminate\Database\Eloquent\Model
     * @throws \Exception
     */
    public function makeAuthorizationCodeProxy()
    {
        return $this->makeProxy('pin_code');
    }

    /**
     * Make token authorization proxy identity
     *
     * @return array
     * @throws \Exception
     */
    public function makeAuthorizationTokenProxy()
    {
        return $this->makeProxy('qr_code');
    }

    /**
     * Make token authorization proxy identity
     *
     * @return array
     * @throws \Exception
     */
    public function makeAuthorizationShortTokenProxy()
    {
        return $this->makeProxy('short_token');
    }

    /**
     * Make email token authorization proxy identity
     *
     * @param string $identityAddress
     * @return array
     * @throws \Exception
     */
    public function makeAuthorizationEmailProxy(string $identityAddress)
    {
        return $this->makeProxy('email_code', $identityAddress);
    }

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
    ) {
        return !!$this->exchangeToken('pin_code', $code, $identityAddress);
    }

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
    ) {
        return !!$this->exchangeToken('qr_code', $token, $identityAddress);
    }

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
    ) {
        return !!$this->exchangeToken('short_token', $token, $identityAddress);
    }

    /**
     * Authorize proxy identity by token
     *
     * @param string $token
     * @return bool|mixed
     */
    public function exchangeAuthorizationShortTokenProxy(string $token)
    {
        $proxy = $this->proxyByExchangeToken($token, 'short_token');

        if (!$proxy) {
            abort(404, trans('identity-proxy.code.not-found'));
        }

        if ($proxy->exchange_time_expired) {
            abort(403, trans('identity-proxy.code.expired'));
        }

        return $proxy->access_token;
    }

    /**
     * Authorize proxy identity by email token
     *
     * @param string $token
     * @return string
     */
    public function activateAuthorizationEmailProxy(string $token)
    {
        return $this->exchangeToken('email_code', $token)->access_token;
    }

    /**
     * Authorize proxy identity by email token
     *
     * @param string $token
     * @return string
     */
    public function exchangeEmailConfirmationToken(string $token)
    {
        return $this->exchangeToken('confirmation_code', $token)->access_token;
    }

    /**
     * Authorize proxy identity by email token
     *
     * @param string $token
     * @return string
     */
    public function exchangeQrCodeToken(string $token)
    {
        return $this->exchangeToken('qr_code', $token)->access_token;
    }

    /**
     * @param $exchange_token
     * @param $type
     * @return IdentityProxy
     */
    private function proxyByExchangeToken($exchange_token, $type)
    {
        return IdentityProxy::query()->where([
            'exchange_token'    => $exchange_token,
            'type'              => $type
        ])->first();
    }

    /**
     * Activate proxy by exchange_token
     *
     * @param string $type
     * @param string $exchange_token
     * @param string $identity_address
     * @return IdentityProxy
     */
    private function exchangeToken(
        string $type,
        string $exchange_token,
        string $identity_address = null
    ) {
        $proxy = $this->proxyByExchangeToken($exchange_token, $type);

        if (!$proxy) {
            abort(404, trans('identity-proxy.code.not-found'));
        }

        if ($proxy->state != 'pending') {
            abort(403, trans('identity-proxy.code.not-pending'));
        }

        if ($proxy->exchange_time_expired) {
            abort(403, trans('identity-proxy.code.expired'));
        }

        // Update identity_address only if provided
        $proxy->update(array_merge([
            'state' => 'active',
        ], $identity_address ? compact('identity_address') : []));

        $initialEmail = $proxy->identity->initial_email;
        $isEmailToken = in_array($type, [
            'email_code', 'confirmation_code'
        ]);

        if ($isEmailToken && $initialEmail && !$initialEmail->verified) {
            $proxy->identity->initial_email->setVerified();
        }

        return $proxy;
    }

    /**
     * @param $size
     * @return string
     */
    private function makeToken($size)
    {
        return app('token_generator')->generate($size);
    }

    /**
     * @return string
     */
    private function makeAccessToken()
    {
        return $this->makeToken(200);
    }

    /**
     * @param string $identity_address
     * @return string|null
     */
    public function getPrimaryEmail(string $identity_address): ?string {
        return IdentityEmail::where([
            'primary' => 1,
            'identity_address' => $identity_address
            ])->first()->email ?? null;
    }

    /**
     * @param string $primary_email
     * @return string|null
     */
    public function getAddress(string $primary_email): ?string {
        return IdentityEmail::where([
            'primary' => true,
            'email' => $primary_email
            ])->first()->identity_address ?? null;
    }

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
    ): IdentityEmail {
        return Identity::whereAddress($identity_address)->first()->addEmail(
            $primary_email,
            $verified,
            $primary
        );
    }

    /**
     * @param string $email
     * @return bool
     */
    public function isEmailAvailable(
        string $email
    ): bool {
        return !IdentityEmail::whereEmail($email)->exists();
    }

    /**
     * Search identity addresses by email substring
     * @param string $search
     * @return array
     */
    public function identityAddressesByEmailSearch(string $search): array
    {
        return IdentityEmail::where('email', 'LIKE', "%$search%")
            ->where('primary', true)
            ->pluck('identity_address')
            ->toArray();
    }
}