<?php

namespace App\Models;

use App\Services\BankService\Models\Bank;
use App\Services\EventLogService\Traits\HasLogs;
use bunq\Context\ApiContext;
use bunq\Context\BunqContext;
use bunq\Exception\ForbiddenException;
use bunq\Model\Core\BunqEnumOauthResponseType;
use bunq\Model\Core\OauthAuthorizationUri;
use bunq\Model\Generated\Endpoint\MonetaryAccount;
use bunq\Model\Generated\Endpoint\MonetaryAccountBank;
use bunq\Model\Generated\Endpoint\Payment;
use bunq\Model\Generated\Object\Pointer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Throwable;

/**
 * App\Models\BankConnection
 *
 * @property int $id
 * @property int $bank_id
 * @property int $organization_id
 * @property int $implementation_id
 * @property int|null $bank_connection_account_id
 * @property string $redirect_token
 * @property string $access_token
 * @property string $code
 * @property array $context
 * @property \Illuminate\Support\Carbon|null $session_expire_at
 * @property string $state
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Bank $bank
 * @property-read \App\Models\Implementation $implementation
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\EventLogService\Models\EventLog[] $logs
 * @property-read int|null $logs_count
 * @property-read \App\Models\Organization $organization
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\VoucherTransactionBulk[] $voucher_transaction_bulks
 * @property-read int|null $voucher_transaction_bulks_count
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection query()
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection whereAccessToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection whereBankConnectionAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection whereBankId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection whereContext($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection whereImplementationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection whereRedirectToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection whereSessionExpireAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class BankConnection extends Model
{
    use HasLogs;

    public const EVENT_CREATED = 'created';
    public const EVENT_REPLACED = 'replaced';
    public const EVENT_REJECTED = 'rejected';
    public const EVENT_DISABLED = 'disabled';
    public const EVENT_ACTIVATED = 'activated';
    public const EVENT_DISABLED_INVALID = 'disabled_invalid';

    public const EVENTS = [
        self::EVENT_CREATED,
        self::EVENT_REPLACED,
        self::EVENT_REJECTED,
        self::EVENT_DISABLED,
        self::EVENT_ACTIVATED,
        self::EVENT_DISABLED_INVALID,
    ];

    public const STATE_ACTIVE = 'active';
    public const STATE_EXPIRED = 'expired';
    public const STATE_PENDING = 'pending';
    public const STATE_REPLACED = 'replaced';
    public const STATE_REJECTED = 'rejected';
    public const STATE_DISABLED = 'disabled';
    public const STATE_INVALID = 'invalid';

    public const STATES = [
        self::STATE_ACTIVE,
        self::STATE_EXPIRED,
        self::STATE_PENDING,
        self::STATE_REPLACED,
        self::STATE_REJECTED,
        self::STATE_DISABLED,
        self::STATE_INVALID,
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'bank_id', 'organization_id', 'implementation_id', 'monetary_account_id',
        'monetary_account_iban', 'redirect_token', 'access_token', 'code', 'state',
        'context', 'session_expire_at',
    ];

    /**
     * @var string[]
     */
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
     * @var string[]
     */
    protected $dates = [
        'session_expire_at',
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
     * @param Employee $employee
     * @param Organization $organization
     * @param Implementation $implementation
     * @return BankConnection|Model
     */
    public static function addConnection(
        Bank $bank,
        Employee $employee,
        Organization $organization,
        Implementation $implementation
    ): BankConnection {
        $bankConnection = static::create([
            'bank_id' => $bank->id,
            'organization_id' => $organization->id,
            'implementation_id' => $implementation->id,
            'redirect_token' => token_generator_db(static::query(), 'redirect_token', 128),
            'state' => BankConnection::STATE_PENDING,
        ]);

        $bankConnection->log($bankConnection::EVENT_CREATED, $bankConnection->getLogModels($employee));

        return $bankConnection;
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
        /** @var BankConnection[] $activeConnections */
        $activeConnections = $this->organization->bank_connections()->where([
            'state' => static::STATE_ACTIVE,
        ])->get();

        foreach ($activeConnections as $bankConnection) {
            $bankConnection->updateModel([
                'state' => static::STATE_REPLACED,
            ])->log($bankConnection::EVENT_REPLACED, $bankConnection->getLogModels());
        }

        $this->update([
            'state' => static::STATE_ACTIVE,
        ]);

        $this->log(static::EVENT_ACTIVATED, $this->getLogModels());

        return $this;
    }

    /**
     * @return $this
     */
    public function setRejected(): self
    {
        $this->update([
            'state' => static::STATE_REJECTED,
        ]);

        $this->log(static::EVENT_REJECTED, $this->getLogModels());

        return $this;
    }

    /**
     * @return void
     */
    public function useContext(): bool
    {
        try {
            BunqContext::loadApiContext(ApiContext::fromJson(json_encode($this->context)));
            return true;
        } catch (Throwable $e) {
            if ($e instanceof ForbiddenException) {
                $this->disableAsInvalid($e->getMessage());
            }
        }

        return false;
    }

    /**
     * @return ?array
     */
    public function getMonetaryAccounts(): ?array
    {
        if (!$this->useContext()) {
            return null;
        }

        $bankAccountBanks = array_map(function (MonetaryAccount $monetaryAccount) {
           return $monetaryAccount->getMonetaryAccountBank();
        }, MonetaryAccount::listing()->getValue());

        return array_map(function(MonetaryAccountBank $bankAccount) {
            return array_merge([
                'id' => $bankAccount->getId()
            ], array_reduce($bankAccount->getAlias(), function(array $arr, Pointer $pointer) {
                return array_merge($arr, [strtolower($pointer->getType()) => $pointer->getValue()]);
            }, []));
        }, array_filter($bankAccountBanks));
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
    public function disable(Employee $employee): self
    {
        $this->updateModel([
            'state' => static::STATE_DISABLED,
        ]);

        $this->log(static::EVENT_DISABLED, $this->getLogModels($employee));

        return $this;
    }

    /**
     * @return $this
     */
    public function disableAsInvalid(string $errorMessage): self
    {
        $this->updateModel([
            'state' => static::STATE_INVALID,
        ]);

        $this->log(static::EVENT_DISABLED_INVALID, $this->getLogModels(), [
            'bank_connection_error_message' => $errorMessage,
        ]);

        return $this;
    }

    /**
     * @return string|null
     */
    public function fetchActiveMonetaryAccountIban(): ?string
    {
        if (!$monetaryAccounts = $this->getMonetaryAccounts()) {
            return null;
        }

        return array_filter($monetaryAccounts, function(array $account) {
            return $account['id'] == $this->monetary_account_id;
        })[0]['iban'] ?? null;
    }

    /**
     * @param int $count
     * @return Payment[]
     */
    public function fetchPayments($count = 100): array
    {
        if (!$this->useContext()) {
            return [];
        }

        return Payment::listing(null, compact('count'))->getValue();
    }

    /**
     * @param Employee|null $employee
     * @param array $extraModels
     * @return array
     */
    protected function getLogModels(?Employee $employee = null, array $extraModels = []): array
    {
        return array_merge([
            'employee' => $employee,
            'organization' => $this->organization,
            'bank_connection' => $this,
        ], $extraModels);
    }
}
