<?php
namespace App\Services\Forus\Identity\Repositories;

use App\Services\Forus\Identity\Models\Identity;
use App\Services\Forus\Identity\Models\IdentityProxy;

class IdentityRepo implements Interfaces\IIdentityRepo
{
    protected $model;
    protected $recordRepo;

    /**
     * How many time user have to exchange their exchange_token
     * @var array
     */
    protected $expirationTimes = [
        // 10 minutes
        'pin_code' => 60 * 10,
        // 60 minutes
        'qr_code' => 60 * 60,
        // 60 minutes
        'email_code' => 60 * 60,
        // 1 month
        'confirmation_code' => 60 * 60 * 24 * 30,
    ];

    public function __construct(
        Identity $model
    ) {
        $this->model = $model;
        $this->recordRepo = app('forus.services.record');
    }

    /**
     * Make new identity
     * @param string $pinCode
     * @param array $records
     * @throws \Exception
     * @return mixed
     */
    public function make(
        string $pinCode,
        array $records = []
    ) {
        $identity = $this->model->create(collect(
            app('key_pair_generator')->make()
        )->merge([
            'pin_code' => app('hash')->make($pinCode)
        ])->toArray())->toArray();

        $this->recordRepo->updateRecords($identity['address'], $records);

        return $identity['address'];
    }

    /**
     * Create new proxy for given identity
     *
     * @param string $identity
     * @return array|\Illuminate\Support\Collection
     * @throws \Exception
     */
    public function makeIdentityPoxy(
        $identity
    ) {
        return $this->makeProxy('confirmation_code', $identity);
    }

    /**
     * Get access_token by proxy identity id
     * @param $proxyIdentityId
     * @return mixed
     * @throws \Exception
     */
    public function getProxyAccessToken(
        $proxyIdentityId
    ) {
        $proxyIdentity = IdentityProxy::whereKey($proxyIdentityId)->first();

        if (!$proxyIdentity) {
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
        $proxyIdentity = IdentityProxy::query()->where([
            'access_token' => $access_token
        ])->first();

        return $proxyIdentity ? $proxyIdentity->id : null;
    }

    /**
     * Get proxy identity by access token
     * @param mixed $proxyIdentityId
     * @return string
     */
    public function identityAddressByProxyId(
        $proxyIdentityId = null
    ) {
        $proxyIdentity = IdentityProxy::whereKey($proxyIdentityId)->first();

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
    public function proxyStateById(
        $proxyIdentityId = null
    ) {
        $proxyIdentity = IdentityProxy::whereKey($proxyIdentityId)->first();

        return $proxyIdentity ? $proxyIdentity->state : null;
    }

    /**
     * Destroy proxy identity by id
     * @param mixed $proxyIdentityId
     * @return mixed|void
     * @throws \Exception
     */
    public function destroyProxyIdentity(
        $proxyIdentityId
    ) {
        IdentityProxy::whereKey($proxyIdentityId)->delete();
    }

    /**
     * @param $proxyIdentityId
     * @return bool
     * @throws \Exception
     */
    public function hasPinCode($proxyIdentityId) {
        $proxyIdentity = IdentityProxy::whereKey($proxyIdentityId)->first();

        if (!$proxyIdentity) {
            throw new \Exception(
                trans('identity.exceptions.unknown_identity')
            );
        }

        return !!$proxyIdentity->identity->pin_code;
    }

    /**
     * @param mixed $proxyIdentityId
     * @param string $pinCode
     * @return bool
     * @throws \Exception
     */
    public function cmpPinCode(
        $proxyIdentityId,
        $pinCode
    ) {
        $proxyIdentity = IdentityProxy::whereKey($proxyIdentityId)->first();

        if (!$proxyIdentity) {
            throw new \Exception(
                trans('identity.exceptions.unknown_identity')
            );
        }

        return app('hash')->check(
            $pinCode,
            $proxyIdentity->identity->pin_code
        );
    }

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
    ) {
        $proxyIdentity = IdentityProxy::whereKey($proxyIdentityId)->first();


        if (!$proxyIdentity) {
            throw new \Exception(
                trans('identity.exceptions.unknown_identity')
            );
        }

        if ($this->hasPinCode($proxyIdentityId) && !$this->cmpPinCode($proxyIdentityId, $oldPinCode)) {
            throw  new \Exception(
                trans('identity.exceptions.invalid_pin_code')
            );
        }

        $proxyIdentity->identity->update([
            'pin_code'  => app('hash')->make($pinCode)
        ]);

        return true;
    }

    /**
     * @param $type
     * @return int|string
     * @throws \Exception
     */
    private function uniqExchangeToken($type) {
        do {
            switch ($type) {
                case "pin_code": $token = random_int(111111, 999999); break;
                case "qr_code": $token = $this->makeToken(64); break;
                case "email_code": $token = $this->makeToken(128); break;
                case "confirmation_code": $token = $this->makeToken(200); break;
                default: throw new \Exception(trans('identity-proxy.unknown_token_type')); break;
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
     * @return array
     * @throws \Exception
     */
    public function makeProxy(
        string $type,
        string $identityAddress = null,
        string $state = 'pending'
    ) {
        return $this->createProxy(
            $this->uniqExchangeToken($type),
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

          return collect(IdentityProxy::create(compact(
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
    public function makeAuthorizationCodeProxy() {
        return $this->makeProxy('pin_code');
    }

    /**
     * Make token authorization proxy identity
     *
     * @return array
     * @throws \Exception
     */
    public function makeAuthorizationTokenProxy() {
        return $this->makeProxy('qr_code');
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
     * Authorize proxy identity by email token
     *
     * @param string $token
     * @return string
     */
    public function activateAuthorizationEmailProxy(
        string $token
    ) {
        return $this->exchangeToken('email_code', $token)->access_token;
    }

    /**
     * Authorize proxy identity by email token
     *
     * @param string $token
     * @return string
     */
    public function exchangeEmailConfirmationToken(
        string $token
    ) {
        return $this->exchangeToken('confirmation_code', $token)->access_token;
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
        /** @var IdentityProxy $proxy */
        $proxy = IdentityProxy::query()->where([
            'exchange_token'    => $exchange_token,
            'type'              => $type
        ])->first();

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

        return $proxy;
    }

    private function makeToken($size) {
        return app('token_generator')->generate($size);
    }

    private function makeAccessToken() {
        return $this->makeToken(200);
    }
}