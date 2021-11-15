<?php

namespace App\Models;

use App\Services\BankService\Models\Bank;
use bunq\Context\ApiContext;
use bunq\Context\BunqContext;
use bunq\Model\Core\BunqEnumOauthResponseType;
use bunq\Model\Core\OauthAuthorizationUri;
use bunq\Model\Generated\Endpoint\MonetaryAccount;
use bunq\Model\Generated\Endpoint\Payment;
use bunq\Model\Generated\Object\Pointer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\BankConnection
 *
 * @property int $id
 * @property int $bank_id
 * @property int $organization_id
 * @property int $implementation_id
 * @property string $monetary_account_id
 * @property string $monetary_account_iban
 * @property string $redirect_token
 * @property string $access_token
 * @property string $code
 * @property array $context
 * @property string|null $session_expire_at
 * @property string $state
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Bank $bank
 * @property-read \App\Models\Implementation $implementation
 * @property-read \App\Models\Organization $organization
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\VoucherTransactionBulk[] $voucher_transaction_bulks
 * @property-read int|null $voucher_transaction_bulks_count
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection query()
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection whereAccessToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection whereBankId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection whereContext($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection whereImplementationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection whereMonetaryAccountIban($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection whereMonetaryAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection whereRedirectToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection whereSessionExpireAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class BankConnection extends Model
{
    public const STATE_ACTIVE = 'active';
    public const STATE_EXPIRED = 'expired';
    public const STATE_PENDING = 'pending';
    public const STATE_REPLACED = 'replaced';
    public const STATE_REJECTED = 'rejected';
    public const STATE_DISABLED = 'disabled';

    public const STATES = [
        self::STATE_ACTIVE,
        self::STATE_EXPIRED,
        self::STATE_PENDING,
        self::STATE_REPLACED,
        self::STATE_REJECTED,
        self::STATE_DISABLED,
    ];

    protected $fillable = [
        'bank_id', 'organization_id', 'implementation_id', 'monetary_account_id',
        'monetary_account_iban', 'redirect_token', 'access_token', 'code', 'state',
        'context', 'session_expire_at',
    ];

    protected $hidden = [
        'redirect_token', 'access_token', 'code', 'context',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'context' => 'array',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function implementation(): BelongsTo
    {
        return $this->belongsTo(Implementation::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function voucher_transaction_bulks(): HasMany
    {
        return $this->hasMany(VoucherTransactionBulk::class);
    }

    /**
     * @param Bank $bank
     * @param Organization $organization
     * @param Implementation $implementation
     * @return BankConnection|Model
     */
    public static function addConnection(
        Bank $bank,
        Organization $organization,
        Implementation $implementation
    ): BankConnection {
        return static::create([
            'bank_id' => $bank->id,
            'organization_id' => $organization->id,
            'implementation_id' => $implementation->id,
            'redirect_token' => token_generator_db(static::query(), 'redirect_token', 128),
            'state' => BankConnection::STATE_PENDING,
        ]);
    }

    /**
     * @return string
     */
    public function getOauthUrl(): string
    {
        $this->bank->useContext();

        return OauthAuthorizationUri::create(
            BunqEnumOauthResponseType::CODE(),
            $this->bank->oauth_redirect_url,
            $this->bank->getOauthClient(),
            $this->redirect_token
        )->getAuthorizationUriString();
    }

    /**
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->state == static::STATE_PENDING;
    }

    /**
     * @param string $code
     * @return string
     */
    public function getTokenByCode(string $code): string
    {
        $this->bank->useContext();

        return $this->bank->exchangeCode($code);
    }

    /**
     * @param string $description
     * @return ApiContext
     */
    public function makeNewContext(string $description = 'PISP Connection'): ApiContext
    {
        $this->bank->useContext();

        return ApiContext::create(
            $this->bank->getContext()->getEnvironmentType(),
            $this->access_token,
            $description
        );
    }

    /**
     * @param ApiContext $apiContext
     * @return BankConnection
     * @throws \bunq\Exception\BunqException
     */
    public function updateContext(ApiContext $apiContext): self
    {
        return tap($this)->update([
            'context' => json_decode($apiContext->toJson()),
            'session_expire_at' => $apiContext->getSessionContext()->getExpiryTime(),
        ]);
    }

    /**
     * @param array $monetaryAccount
     * @return BankConnection
     */
    public function updateMonetaryAccount(array $monetaryAccount): self
    {
        return tap($this)->update([
            'monetary_account_id' => $monetaryAccount['id'] ?? null,
            'monetary_account_iban' => $monetaryAccount['iban'] ?? null,
        ]);
    }

    /**
     * @return BankConnection
     */
    public function setActive(): self
    {
        $this->organization->bank_connections()->where([
            'state' => static::STATE_ACTIVE,
        ])->update([
            'state' => static::STATE_REPLACED,
        ]);

        return tap($this)->update([
            'state' => static::STATE_ACTIVE,
        ]);
    }

    /**
     * @return $this
     */
    public function setRejected(): self
    {
        return tap($this)->update([
            'state' => static::STATE_REJECTED,
        ]);
    }

    /**
     * @return void
     */
    public function useContext(): void
    {
        BunqContext::loadApiContext(ApiContext::fromJson(json_encode($this->context)));
    }

    /**
     * @return array
     */
    public function getMonetaryAccounts(): array
    {
        $this->useContext();

        return array_map(function($monetaryAccount) {
            $bankAccount = $monetaryAccount->getMonetaryAccountBank();

            return array_merge([
                'id' => $bankAccount->getId()
            ], array_reduce($bankAccount->getAlias(), function(array $arr, Pointer $pointer) {
                return array_merge($arr, [strtolower($pointer->getType()) => $pointer->getValue()]);
            }, []));
        }, MonetaryAccount::listing()->getValue());
    }

    /**
     * @return ApiContext
     */
    public function getContext(): ApiContext
    {
        return ApiContext::fromJson(json_encode($this->context));
    }

    /**
     * @return $this
     */
    public function disable(): self
    {
        return tap($this)->update([
            'state' => static::STATE_DISABLED,
        ]);
    }

    /**
     * @return string|null
     */
    public function fetchActiveMonetaryAccountIban(): ?string {

        $this->useContext();

        return array_filter($this->getMonetaryAccounts(), function(array $account) {
            return $account['id'] == $this->monetary_account_id;
        })[0]['iban'] ?? null;
    }

    /**
     * @param int $count
     * @return Payment[]
     */
    public function fetchPayments($count = 100): array
    {
        $this->useContext();

        return Payment::listing(null, compact('count'))->getValue();
    }

    public function updateSession()
    {
        $this->useContext();

    }
}
