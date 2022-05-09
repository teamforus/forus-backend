<?php

namespace App\Services\BankService\Models;

use bunq\Context\ApiContext;
use bunq\Context\BunqContext;
use bunq\Model\Core\BunqEnumOauthGrantType;
use bunq\Model\Core\OauthAccessToken;
use bunq\Model\Generated\Endpoint\OauthClient;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Services\BankService\Models\Bank
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Bank newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Bank newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Bank query()
 * @mixin \Eloquent
 */
class Bank extends Model
{
    public const BANK_BNG = 'bng';
    public const BANK_BUNQ = 'bunq';

    /**
     * @var array
     */
    protected $fillable = [];

    /**
     * @var string[]
     */
    protected $hidden = [
        'data'
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'data' => 'array',
    ];

    /**
     * @param array $params optional
     * @return string
     */
    public function buildOauthRedirectUrl(array $params = []): string
    {
        return implode("?", array_filter([
            url('/api/v1/platform/bank-connections/redirect'),
            http_build_query($params),
        ]));
    }

    /**
     * @return void
     */
    public function useContext(): void
    {
        BunqContext::loadApiContext(ApiContext::fromJson(json_encode($this->data['context'])));
    }

    /**
     * @param string $code
     * @return string
     */
    public function exchangeCode(string $code): string
    {
        return OauthAccessToken::create(
            BunqEnumOauthGrantType::AUTHORIZATION_CODE(),
            $code,
            $this->oauth_redirect_url,
            $this->getOauthClient()
        )->getAccessTokenString();
    }

    /**
     * @return \bunq\Model\Core\BunqModel|OauthClient
     */
    public function getOauthClient(): OauthClient
    {
        return OauthClient::createFromJsonString(json_encode($this->data['oauth_client']));
    }

    /**
     * @return ApiContext
     */
    public function getContext(): ApiContext
    {
        return ApiContext::fromJson(json_encode($this->data['context']));
    }

    /**
     * @return bool
     */
    public function isBunq(): bool
    {
        return $this->key === static::BANK_BUNQ;
    }

    /**
     * @return bool
     */
    public function isBNG(): bool
    {
        return $this->key === static::BANK_BNG;
    }
}
