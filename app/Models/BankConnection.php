<?php

namespace App\Models;

use App\Events\BankConnections\BankConnectionActivated;
use App\Events\BankConnections\BankConnectionCreated;
use App\Events\BankConnections\BankConnectionDisabled;
use App\Events\BankConnections\BankConnectionDisabledInvalid;
use App\Events\BankConnections\BankConnectionMonetaryAccountChanged;
use App\Events\BankConnections\BankConnectionReplaced;
use App\Models\Traits\HasDbTokens;
use App\Services\BankService\Models\Bank;
use App\Services\BankService\Values\BankBalance;
use App\Services\BankService\Values\BankMonetaryAccount;
use App\Services\BankService\Values\BankPayment;
use App\Services\BNGService\Exceptions\ApiException;
use App\Services\BNGService\Responses\TransactionValue;
use App\Services\EventLogService\Models\EventLog;
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
 * @property-read Bank|null $bank
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\BankConnectionAccount[] $bank_connection_accounts
 * @property-read int|null $bank_connection_accounts_count
 * @property-read \App\Models\BankConnectionAccount|null $bank_connection_default_account
 * @property-read string|null $iban
 * @property-read \App\Models\Implementation|null $implementation
 * @property-read \Illuminate\Database\Eloquent\Collection|EventLog[] $logs
 * @property-read int|null $logs_count
 * @property-read \App\Models\Organization|null $organization
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\VoucherTransactionBulk[] $voucher_transaction_bulks
 * @property-read int|null $voucher_transaction_bulks_count
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnection query()
 * @mixin \Eloquent
 */
class BankConnection extends Model
{
    use HasLogs, HasDbTokens;

    public const EVENT_CREATED = 'created';
    public const EVENT_REPLACED = 'replaced';
    public const EVENT_REJECTED = 'rejected';
    public const EVENT_DISABLED = 'disabled';
    public const EVENT_ACTIVATED = 'activated';
    public const EVENT_DISABLED_INVALID = 'disabled_invalid';
    public const EVENT_MONETARY_ACCOUNT_CHANGED = 'monetary_account_changed';
    public const EVENT_ERROR = 'error';

    public const EVENTS = [
        self::EVENT_CREATED,
        self::EVENT_REPLACED,
        self::EVENT_REJECTED,
        self::EVENT_DISABLED,
        self::EVENT_ACTIVATED,
        self::EVENT_DISABLED_INVALID,
        self::EVENT_MONETARY_ACCOUNT_CHANGED,
        self::EVENT_ERROR,
    ];

    public const STATE_ACTIVE = 'active';
    public const STATE_EXPIRED = 'expired';
    public const STATE_PENDING = 'pending';
    public const STATE_REPLACED = 'replaced';
    public const STATE_REJECTED = 'rejected';
    public const STATE_DISABLED = 'disabled';
    public const STATE_INVALID = 'invalid';
    public const STATE_ERROR = 'error';

    public const STATES = [
        self::STATE_ACTIVE,
        self::STATE_EXPIRED,
        self::STATE_PENDING,
        self::STATE_REPLACED,
        self::STATE_REJECTED,
        self::STATE_DISABLED,
        self::STATE_INVALID,
        self::STATE_ERROR,
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'bank_id', 'organization_id', 'implementation_id', 'redirect_token', 'access_token',
        'code', 'state', 'context', 'session_expire_at', 'bank_connection_account_id',
        'auth_url', 'auth_params', 'consent_id',
    ];

    /**
     * @var string[]
     */
    protected $hidden = [
        'redirect_token', 'access_token', 'code', 'context', 'auth_url', 'auth_params',
        'consent_id',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'context' => 'array',
        'auth_params' => 'array',
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function bank_connection_accounts(): HasMany
    {
        return $this->hasMany(BankConnectionAccount::class);
    }

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function bank_connection_default_account(): BelongsTo
    {
        return $this->belongsTo(BankConnectionAccount::class, 'bank_connection_account_id');
    }

    /**
     * @return string|null
     * @noinspection PhpUnused
     */
    public function getIbanAttribute(): ?string
    {
        return $this->bank_connection_default_account->monetary_account_iban ?? null;
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
            'redirect_token' => static::makeUniqueToken('redirect_token', 200),
            'state' => BankConnection::STATE_PENDING,
        ]);

        BankConnectionCreated::dispatch($bankConnection, $employee);

        return $bankConnection;
    }

    /**
     * @return string
     */
    public function makeOauthUrl(): ?string
    {
        $auth_url = null;

        if ($this->bank->isBunq()) {
            $auth_url = $this->makeOauthUrlBunq();
        }

        if ($this->bank->isBNG()) {
            $auth_url = $this->makeOauthUrlBNG();
        }

        return $auth_url;
    }

    /**
     * @return string
     */
    protected function makeOauthUrlBunq(): string
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
     * @return string
     */
    protected function makeOauthUrlBNG(): ?string
    {
        $bngService = resolve('bng_service');

        try {
            $response = $bngService->makeAccountConsentRequest($this->redirect_token);
            $authData = $response->getAuthData();
            $consentId = $response->getConsentId();

            $this->update([
                'auth_url' => $authData->getUrl(),
                'auth_params' => $authData->getParams(),
                'consent_id' => $consentId,
            ]);
        } catch (ApiException $exception) {
            $error_message = $exception->getMessage();
            $this->logError(compact('error_message'));
            logger()->error($error_message);

            return null;
        }

        return $authData->getUrl();
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
     * @param BankMonetaryAccount[] $bankMonetaryAccounts
     * @return BankConnection
     */
    public function setMonetaryAccounts(array $bankMonetaryAccounts): self
    {
        foreach ($bankMonetaryAccounts as $index => $bankMonetaryAccount) {
            /** @var BankConnectionAccount $account */
            $account = $this->bank_connection_accounts()->create([
                'monetary_account_id' => $bankMonetaryAccount->getId(),
                'monetary_account_iban' => $bankMonetaryAccount->getIban(),
                'monetary_account_name' => $bankMonetaryAccount->getName(),
            ]);

            if ($index === 0) {
                $this->update([
                    'bank_connection_account_id' => $account->id,
                ]);
            }
        }

        return $this;
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
            BankConnectionReplaced::dispatch($bankConnection->updateModel([
                'state' => static::STATE_REPLACED,
            ]));
        }

        BankConnectionActivated::dispatch($this->updateModel([
            'state' => static::STATE_ACTIVE,
        ]));

        return $this;
    }

    /**
     * @return $this
     */
    public function updateFundBalances(): self
    {
        $this->organization->updateFundBalancesByBankConnection();

        return $this;
    }

    /**
     * @return $this
     */
    public function setRejected(): self
    {
        BankConnectionReplaced::dispatch($this->updateModel([
            'state' => static::STATE_REJECTED,
        ]));

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
            $hasAuthError = strpos($e->getMessage(), "Incorrect API key or IP address") !== false;

            if ($e instanceof ForbiddenException || $hasAuthError) {
                $this->disableAsInvalid($e->getMessage());
            }
        }

        return false;
    }

    /**
     * @return array|null
     */
    public function fetchConnectionMonetaryAccounts(): ?array
    {
        try {
            if ($this->bank->isBNG()) {
                return $this->fetchConnectionMonetaryAccountsBNG();
            }

            if ($this->bank->isBunq()) {
                return $this->fetchConnectionMonetaryAccountsBunq();
            }
        } catch (Throwable $e) {
            logger()->error($e->getMessage());
        }

        return null;
    }

    /**
     * @return array
     * @throws ApiException
     */
    protected function fetchConnectionMonetaryAccountsBNG(): array
    {
        $bngService = resolve('bng_service');

        return array_map(function(array $account) {
            return new BankMonetaryAccount(
                $account['resourceId'] ?? null,
                $account['iban'] ?? null,
                $account['name'] ?? null
            );
        }, $bngService->getAccounts($this->consent_id, $this->access_token)->getAccounts());
    }

    /**
     * @return null|array
     */
    protected function fetchConnectionMonetaryAccountsBunq(): ?array
    {
        if (!$this->useContext()) {
            return null;
        }

        $bankAccountBanks = array_map(function (MonetaryAccount $monetaryAccount) {
            return $monetaryAccount->getMonetaryAccountBank();
        }, MonetaryAccount::listing()->getValue());

        return array_map(function(MonetaryAccountBank $bankAccount) {
            $pointerValues = array_reduce($bankAccount->getAlias(), function(array $arr, Pointer $pointer) {
                return array_merge($arr, [strtolower($pointer->getType()) => $pointer->getValue()]);
            }, []);

            return new BankMonetaryAccount(
                $bankAccount->getId(),
                $pointerValues['iban'],
                $bankAccount->getDescription()
            );
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
        BankConnectionDisabled::dispatch($this->updateModel([
            'state' => static::STATE_DISABLED,
        ]), $employee);

        return $this;
    }

    /**
     * @return $this
     */
    public function disableAsInvalid(string $errorMessage): self
    {
        BankConnectionDisabledInvalid::dispatch($this->updateModel([
            'state' => static::STATE_INVALID,
        ]), null, [
            'bank_connection_error_message' => $errorMessage,
        ]);

        return $this;
    }

    /**
     * @return BankBalance
     */
    public function fetchBalance(): ?BankBalance
    {
        $bank_connection_default_account = $this->bank_connection_default_account;
        $monetary_account_id = $bank_connection_default_account->monetary_account_id ?? null;

        if ($monetary_account_id && $this->bank->isBunq()) {
            return $this->fetchBalanceBunq($monetary_account_id);
        }

        if ($monetary_account_id && $this->bank->isBNG()) {
            return $this->fetchBalanceBNG($monetary_account_id);
        }

        return null;
    }

    /**
     * @param string $monetary_account_id
     * @return BankBalance|null
     */
    protected function fetchBalanceBunq(string $monetary_account_id): ?BankBalance
    {
        if (!$this->useContext()) {
            return null;
        }

        $account = MonetaryAccount::get($monetary_account_id);
        $balance = $account->getValue()->getMonetaryAccountBank()->getBalance();

        return new BankBalance($balance->getValue(), $balance->getCurrency());
    }

    /**
     * @return BankBalance
     */
    public function fetchBalanceBNG(string $monetary_account_id): ?BankBalance
    {
        $bngService = resolve('bng_service');

        try {
            $response = $bngService->getBalance($monetary_account_id, $this->consent_id, $this->access_token);
            $balance = $response->getClosingBookedBalance();

            return new BankBalance($balance->getBalanceAmount(), $balance->getBalanceCurrency());
        } catch (\Throwable $e) {
            logger()->error($e->getMessage());
        }

        return null;
    }

    /**
     * @param int $count
     * @return BankPayment[]
     */
    public function fetchPayments(int $count = 100): array
    {
        $bank_connection_default_account = $this->bank_connection_default_account;
        $monetary_account_id = $bank_connection_default_account->monetary_account_id ?? null;

        if ($monetary_account_id && $this->bank->isBunq()) {
            return $this->fetchPaymentsBunq($monetary_account_id, $count);
        }

        if ($monetary_account_id && $this->bank->isBNG()) {
            return $this->fetchPaymentsBNG($monetary_account_id, $count);
        }

        return [];
    }

    /**
     * @param string $monetary_account_id
     * @param int $count
     * @return BankPayment[]
     */
    protected function fetchPaymentsBunq(string $monetary_account_id, int $count = 100): ?array
    {
        if (!$this->useContext()) {
            return null;
        }

        return array_map(function(Payment $payment) {
            return new BankPayment(
                $payment->getId(),
                $payment->getAmount()->getValue(),
                $payment->getAmount()->getCurrency(),
                $payment->getDescription()
            );
        }, Payment::listing($monetary_account_id, compact('count'))->getValue());
    }

    /**
     * @param string $monetary_account_id
     * @param int $count
     * @return BankPayment[]
     */
    protected function fetchPaymentsBNG(string $monetary_account_id, int $count = 100): ?array
    {
        try {
            $page = 1;
            $transactions = [];
            $bngService = resolve('bng_service');
            $totalPages = $count / 10;

            do {
                $response = $bngService->getTransactions(
                    $monetary_account_id,
                    $this->consent_id,
                    $this->access_token,
                    ['bookingStatus' => 'booked', 'page' => $page++]
                );

                $totalPages = min($totalPages, $response->getTransactionsLinks()->getLast()->getParams()['page']);
                $transactions = array_merge($transactions, $response->getTransactionsBooked());
            } while ($page <= $totalPages);

            return array_map(function(TransactionValue $transaction) {
                return new BankPayment(
                    $transaction->getTransactionId(),
                    $transaction->getTransactionAmount(),
                    $transaction->getTransactionCurrency(),
                    $transaction->getTransactionDescription()
                );
            }, $transactions);
        } catch (Throwable $e) {
            logger()->error($e->getMessage());
        }

        return null;
    }

    /**
     * @param int $bank_connection_account_id
     * @param Employee $employee
     */
    public function switchBankConnectionAccount(int $bank_connection_account_id, Employee $employee)
    {
        if ($bank_connection_account_id != $this->bank_connection_account_id) {
            BankConnectionMonetaryAccountChanged::dispatch($this->updateModel(
                compact('bank_connection_account_id')
            ), $employee);
        }
    }

    /**
     * @param Employee|null $employee
     * @param array $extraModels
     * @return array
     */
    public function getLogModels(?Employee $employee = null, array $extraModels = []): array
    {
        return array_merge([
            'bank' => $this->bank,
            'employee' => $employee,
            'organization' => $this->organization,
            'bank_connection' => $this,
            'bank_connection_account' => $this->bank_connection_default_account,
        ], $extraModels);
    }

    /**
     * @param array $array
     * @param Employee|null $employee
     * @return \App\Services\EventLogService\Models\EventLog|mixed
     */
    public function logError(array $array = [], ?Employee $employee = null): EventLog
    {
        return $this->log(static::EVENT_ERROR, $this->getLogModels($employee), $array);
    }

    /**
     * @param string|null $success
     * @param string|null $error
     * @return string
     */
    public function dashboardDetailsUrl(?string $success = null, ?string $error = null): string
    {
        return $this->implementation->urlSponsorDashboard(sprintf(
            "/organizations/%s/bank-connections",
            $this->organization_id
        ), array_filter(compact('success', 'error')));
    }
}
