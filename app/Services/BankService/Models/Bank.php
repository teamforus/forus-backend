<?php

namespace App\Services\BankService\Models;

use bunq\Context\ApiContext;
use bunq\Context\BunqContext;
use bunq\Model\Core\BunqEnumOauthGrantType;
use bunq\Model\Core\BunqModel;
use bunq\Model\Core\OauthAccessToken;
use bunq\Model\Generated\Endpoint\OauthClient;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Services\BankService\Models\Bank
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string|null $oauth_redirect_id
 * @property string|null $oauth_redirect_url
 * @property array $data
<<<<<<< epic.roi
 * @property string $transaction_cost
=======
 * @property string|null $transaction_cost
>>>>>>> develop
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Bank newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Bank newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Bank query()
 * @method static \Illuminate\Database\Eloquent\Builder|Bank whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bank whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bank whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bank whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bank whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bank whereOauthRedirectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bank whereOauthRedirectUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bank whereTransactionCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bank whereUpdatedAt($value)
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
     * @return BunqModel|OauthClient
     */
    public function getOauthClient(): BunqModel|OauthClient
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
