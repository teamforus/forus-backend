<?php

namespace App\Models;

use App\Events\VoucherTransactions\VoucherTransactionBunqSuccess;
use App\Models\Traits\HasDbTokens;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\VoucherTransactionBulkQuery;
use App\Scopes\Builders\VoucherTransactionQuery;
use App\Services\BankService\Resources\BankResource;
use App\Services\BNGService\BNGService;
use App\Services\BNGService\Data\PaymentInfoData;
use App\Services\BNGService\Exceptions\ApiException;
use App\Services\BNGService\Responses\BulkPaymentValue;
use App\Services\BNGService\Responses\Entries\Account;
use App\Services\BNGService\Responses\Entries\Amount as AmountBNG;
use App\Services\BNGService\Responses\Entries\BulkPayment;
use App\Services\BNGService\Responses\Entries\Payment as PaymentBNG;
use App\Services\BNGService\Responses\Entries\PaymentInitiator;
use App\Services\EventLogService\Models\EventLog;
use App\Services\EventLogService\Traits\HasLogs;
use bunq\Model\Generated\Endpoint\DraftPayment;
use bunq\Model\Generated\Endpoint\Payment;
use bunq\Model\Generated\Endpoint\PaymentBatch;
use bunq\Model\Generated\Object\Amount;
use bunq\Model\Generated\Object\DraftPaymentEntry;
use bunq\Model\Generated\Object\Pointer;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * App\Models\VoucherTransactionBulk
 *
 * @property int $id
 * @property int|null $bank_connection_id
 * @property string|null $payment_id
 * @property string|null $monetary_account_id
 * @property string $monetary_account_iban
 * @property string|null $monetary_account_name
 * @property string|null $code
 * @property string|null $access_token
 * @property string|null $redirect_token
 * @property string|null $auth_url
 * @property string|null $sepa_xml
 * @property \Illuminate\Support\Carbon|null $execution_date
 * @property int|null $implementation_id
 * @property array|null $auth_params
 * @property string $state
 * @property int $accepted_manually
 * @property int $state_fetched_times
 * @property string|null $state_fetched_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\BankConnection|null $bank_connection
 * @property-read string $state_locale
 * @property-read \App\Models\Implementation|null $implementation
 * @property-read Collection|EventLog[] $logs
 * @property-read int|null $logs_count
 * @property-read Collection|\App\Models\VoucherTransaction[] $voucher_transactions
 * @property-read int|null $voucher_transactions_count
 * @method static Builder|VoucherTransactionBulk newModelQuery()
 * @method static Builder|VoucherTransactionBulk newQuery()
 * @method static Builder|VoucherTransactionBulk query()
 * @method static Builder|VoucherTransactionBulk whereAcceptedManually($value)
 * @method static Builder|VoucherTransactionBulk whereAccessToken($value)
 * @method static Builder|VoucherTransactionBulk whereAuthParams($value)
 * @method static Builder|VoucherTransactionBulk whereAuthUrl($value)
 * @method static Builder|VoucherTransactionBulk whereBankConnectionId($value)
 * @method static Builder|VoucherTransactionBulk whereCode($value)
 * @method static Builder|VoucherTransactionBulk whereCreatedAt($value)
 * @method static Builder|VoucherTransactionBulk whereExecutionDate($value)
 * @method static Builder|VoucherTransactionBulk whereId($value)
 * @method static Builder|VoucherTransactionBulk whereImplementationId($value)
 * @method static Builder|VoucherTransactionBulk whereMonetaryAccountIban($value)
 * @method static Builder|VoucherTransactionBulk whereMonetaryAccountId($value)
 * @method static Builder|VoucherTransactionBulk whereMonetaryAccountName($value)
 * @method static Builder|VoucherTransactionBulk wherePaymentId($value)
 * @method static Builder|VoucherTransactionBulk whereRedirectToken($value)
 * @method static Builder|VoucherTransactionBulk whereSepaXml($value)
 * @method static Builder|VoucherTransactionBulk whereState($value)
 * @method static Builder|VoucherTransactionBulk whereStateFetchedAt($value)
 * @method static Builder|VoucherTransactionBulk whereStateFetchedTimes($value)
 * @method static Builder|VoucherTransactionBulk whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class VoucherTransactionBulk extends Model
{
    use HasLogs, HasDbTokens;

    public const EVENT_RESET = 'reset';
    public const EVENT_CREATED = 'created';
    public const EVENT_SUBMITTED = 'submitted';
    public const EVENT_ACCEPTED = 'accepted';
    public const EVENT_REJECTED = 'rejected';
    public const EVENT_ERROR = 'error';

    public const EVENTS = [
        self::EVENT_RESET,
        self::EVENT_CREATED,
        self::EVENT_SUBMITTED,
        self::EVENT_ACCEPTED,
        self::EVENT_REJECTED,
        self::EVENT_ERROR,
    ];

    public const STATE_DRAFT = 'draft';
    public const STATE_ERROR = 'error';
    public const STATE_PENDING = 'pending';
    public const STATE_ACCEPTED = 'accepted';
    public const STATE_REJECTED = 'rejected';

    public const STATES = [
        self::STATE_DRAFT,
        self::STATE_ERROR,
        self::STATE_PENDING,
        self::STATE_ACCEPTED,
        self::STATE_REJECTED,
    ];

    public const SORT_BY_FIELDS = [
        'id', 'amount', 'created_at', 'state', 'voucher_transactions_count',
    ];

    protected $dates = [
        'execution_date',
    ];

    protected $casts = [
        'auth_params' => 'array',
    ];

    protected $hidden = [
        'code', 'sepa_xml', 'auth_url', 'auth_params', 'access_token',
        'redirect_token',
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'bank_connection_id', 'state', 'state_fetched_times', 'state_fetched_at',
        'payment_id', 'accepted_manually', 'monetary_account_id', 'monetary_account_iban',
        'monetary_account_name', 'code', 'access_token', 'redirect_token', 'sepa_xml',
        'execution_date', 'auth_url', 'auth_params', 'implementation_id',
    ];

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function bank_connection(): BelongsTo
    {
        return $this->belongsTo(BankConnection::class);
    }

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function implementation(): BelongsTo
    {
        return $this->belongsTo(Implementation::class);
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function voucher_transactions(): HasMany
    {
        return $this->hasMany(VoucherTransaction::class);
    }

    /**
     * @return BelongsTo
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getStateLocaleAttribute(): string
    {
        return [
            static::STATE_PENDING => 'In afwachting',
            static::STATE_ACCEPTED => 'Geaccepteerd',
            static::STATE_REJECTED => 'Geannuleerd',
            static::STATE_ERROR => 'Error',
            static::STATE_DRAFT => 'In voorbereiding',
        ][$this->state] ?? $this->state;
    }

    /**
     * @return bool
     */
    public function isDraft(): bool
    {
        return $this->state == static::STATE_DRAFT;
    }

    /**
     * @return bool
     */
    public function isRejected(): bool
    {
        return $this->state == static::STATE_REJECTED;
    }

    /**
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->state == static::STATE_PENDING;
    }

    /**
     * @param VoucherTransaction $transaction
     * @param DraftPayment $draftPayment
     * @return Payment|null
     * @throws \bunq\Exception\BunqException
     */
    protected function findPaymentFromDraftPayment(
        VoucherTransaction $transaction,
        DraftPayment $draftPayment
    ): ?Payment {
        $filter = function(Payment $payment) use ($transaction) {
            return strtolower($payment->getDescription()) == strtolower($transaction->payment_description);
        };

        $payments = $draftPayment->getObject()->getReferencedObject();

        if ($payments instanceof PaymentBatch) {
            $payments = $payments->getPayments()->getPayment() ?: [];
        } else {
            $payments = [$payments];
        }

        return array_filter(array_filter($payments), $filter)[0] ?? null;
    }

    /**
     * @return BulkPaymentValue|DraftPayment|null
     */
    public function fetchPayment()
    {
        if ($this->bank_connection->bank->isBunq()) {
            if (!$this->bank_connection->useContext()) {
                return null;
            }

            return DraftPayment::get($this->payment_id, $this->monetary_account_id)->getValue();
        }

        if ($this->bank_connection->bank->isBNG() && $this->access_token) {
            /** @var BNGService $bngService */
            $bngService = resolve('bng_service');

            try {
                return $bngService->getBulkDetails($this->payment_id, $this->access_token);
            } catch (ApiException $exception) {
                logger()->error($exception->getMessage());
            }
        }

        return null;
    }

    /**
     * @param DraftPayment $draftPayment
     * @return VoucherTransactionBulk
     * @throws Throwable
     */
    public function setAccepted(DraftPayment $draftPayment): self
    {
        DB::transaction(function() use ($draftPayment) {
            $this->update([
                'state' => static::STATE_ACCEPTED,
            ]);

            $this->log(static::STATE_ACCEPTED, $this->getLogModels());

            foreach ($this->voucher_transactions as $transaction) {
                $payment = $draftPayment ? $this->findPaymentFromDraftPayment(
                    $transaction, $draftPayment
                ) : null;

                $transaction->forceFill([
                    'state'             => VoucherTransaction::STATE_SUCCESS,
                    'payment_id'        => $payment ? $payment->getId() : null,
                    'payment_time'      => now(),
                ])->save();

                VoucherTransactionBunqSuccess::dispatch($transaction);
            }
        });

        sleep(1);

        return $this->fresh();
    }

    /**
     * @param BulkPaymentValue $draftPayment
     * @return VoucherTransactionBulk
     * @throws Throwable
     */
    public function setAcceptedBNG(BulkPaymentValue $draftPayment): self
    {
        DB::transaction(function() use ($draftPayment) {
            $this->update([
                'state' => static::STATE_ACCEPTED,
            ]);

            $this->log(static::STATE_ACCEPTED, $this->getLogModels());

            foreach ($this->voucher_transactions as $transaction) {
                $transaction->forceFill([
                    'state'             => VoucherTransaction::STATE_SUCCESS,
                    'payment_id'        => null,
                    'payment_time'      => now(),
                ])->save();

                VoucherTransactionBunqSuccess::dispatch($transaction);
            }
        });

        sleep(1);

        return $this->fresh();
    }

    /**
     * @param Employee|null $employee
     * @return $this
     */
    public function submitBulkToBunq(?Employee $employee = null): self
    {
        try {
            DB::transaction(function() use ($employee) {
                if (!$this->bank_connection->useContext()) {
                    throw new Exception("Bank connection invalid.", 403);
                }

                $transactions = $this->voucher_transactions->map(function(VoucherTransaction $transaction) {
                    $amount = number_format($transaction->amount, 2, '.', '');
                    $paymentAmount = new Amount($amount, 'EUR');
                    $paymentPointer = new Pointer('IBAN', $transaction->provider->iban, $transaction->provider->name);
                    $paymentDescription = $transaction->makePaymentDescription();

                    $transaction->update([
                        'iban_to' => $transaction->provider->iban,
                        'iban_from' => $this->monetary_account_iban,
                        'payment_description' => $paymentDescription,
                    ]);

                    return new DraftPaymentEntry($paymentAmount, $paymentPointer, $paymentDescription);
                })->toArray();

                $monetaryAccountId = $this->monetary_account_id;
                $payment = DraftPayment::create($transactions, 1, $monetaryAccountId);

                $this->updateModel([
                    'state' => self::STATE_PENDING,
                    'payment_id' => $payment->getValue(),
                ])->log(self::EVENT_SUBMITTED, $this->getLogModels($employee));

                return $this;
            });

            // This endpoint is throttled by bunq: You can do a maximum of 3 calls per 3 second to this endpoint.
            sleep(2);
        } catch (Throwable $e) {
            logger()->error($e->getMessage() . "\n" . $e->getTraceAsString());

            $this->updateModel([
                'state' => self::STATE_ERROR,
            ])->logError([
                'error_message' => $e->getMessage(),
            ], $employee);
        }

        return $this;
    }

    /**
     * @param Employee|null $employee
     * @param Implementation|null $implementation
     * @return $this
     */
    public function submitBulkToBNG(
        ?Employee $employee = null,
        ?Implementation $implementation = null
    ): self {
        try {
            $implementation = $implementation ?: Implementation::general();
            $bngService = resolve('bng_service');

            DB::transaction(function() use ($employee, $bngService, $implementation) {
                $payments = [];
                $requestedExecutionDate = PaymentBNG::getNextBusinessDay()->format('Y-m-d');

                foreach ($this->voucher_transactions as $transaction) {
                    $transaction->update([
                        'iban_to' => $transaction->provider->iban,
                        'iban_from' => $this->monetary_account_iban,
                        'payment_description' => $transaction->makePaymentDescription(),
                    ]);

                    $payments[] = new PaymentBNG(
                        new AmountBNG(number_format($transaction->amount, 2, '.', ''), 'EUR'),
                        new Account($this->monetary_account_iban, $this->monetary_account_name),
                        new Account($transaction->provider->iban, $transaction->provider->name),
                        $transaction->id,
                        $transaction->payment_description,
                        $requestedExecutionDate
                    );
                }

                $redirectToken = static::makeUniqueToken('redirect_token', 200);

                $bulkPayment = new BulkPayment(
                    new PaymentInitiator(Arr::get($this->bank_connection->bank->data, 'paymentInitiatorName')),
                    new Account($this->monetary_account_iban, $this->monetary_account_name),
                    $payments,
                    new PaymentInfoData($this->id, $requestedExecutionDate, $redirectToken),
                    token_generator()->generate(32)
                );

                $response = $bngService->bulkPayment($bulkPayment);

                $this->updateModel([
                    'state' => self::STATE_PENDING,
                    'payment_id' => $response->getPaymentId(),
                    'auth_url' => $response->getAuthData()->getUrl(),
                    'sepa_xml' => $bulkPayment->toXml(),
                    'redirect_token' => $bulkPayment->getRedirectToken(),
                    'auth_params' => $response->getAuthData()->getParams(),
                    'execution_date' => $requestedExecutionDate,
                    'implementation_id' => $implementation->id,
                ])->log(self::EVENT_SUBMITTED, $this->getLogModels($employee));

                return $this;
            });

            // Throttle calls just in case
            sleep(2);
        } catch (Throwable $e) {
            logger()->error($e->getMessage() . "\n" . $e->getTraceAsString());

            $this->updateModel([
                'state' => self::STATE_ERROR,
            ])->logError([
                'error_message' => $e->getMessage(),
            ], $employee);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function setRejected(): self
    {
        $this->updateModel([
            'state' => static::STATE_REJECTED,
        ])->log(static::STATE_REJECTED, $this->getLogModels());

        return $this;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \Throwable
     */
    public static function buildBulks(): void
    {
        /** @var Organization[]|Collection $sponsors */
        $sponsors = Organization::whereHas('funds', function(Builder $builder) {
            FundQuery::whereIsInternal($builder);
            FundQuery::whereIsConfiguredByForus($builder);

            $builder->whereHas('voucher_transactions', function(Builder $builder) {
                VoucherTransactionQuery::whereAvailableForBulking($builder);
            });
        })->whereHas('bank_connection_active', function(Builder $builder) {
            $builder->whereHas('bank_connection_default_account');
        })->get();

        foreach ($sponsors as $sponsor) {
            self::buildBulksForOrganization($sponsor);
        }
    }

    /**
     * @param Organization $sponsor
     * @return Builder
     */
    public static function getNextBulkTransactionsForSponsor(Organization $sponsor): Builder
    {
        $query = VoucherTransaction::whereHas('voucher', function(Builder $builder) use ($sponsor) {
            $builder->whereHas('fund', function(Builder $builder) use ($sponsor) {
                $builder->where('funds.organization_id', $sponsor->id);
            });
        });

        return VoucherTransactionQuery::whereAvailableForBulking($query);
    }

    /**
     * @param Organization $sponsor
     * @param Employee|null $employee
     * @param array $previousBulks
     * @return array
     */
    public static function buildBulksForOrganization(
        Organization $sponsor,
        ?Employee $employee = null,
        array $previousBulks = []
    ): array {
        $perBulk = 100;
        $query = static::getNextBulkTransactionsForSponsor($sponsor);

        if ((clone($query))->doesntExist()) {
            return $previousBulks;
        }

        /** @var VoucherTransactionBulk $transactionsBulk */
        $defaultAccount = $sponsor->bank_connection_active->bank_connection_default_account;
        $transactionsBulk = $sponsor->bank_connection_active->voucher_transaction_bulks()->create([
            'state' => VoucherTransactionBulk::STATE_DRAFT,
            'monetary_account_id' => $defaultAccount->monetary_account_id,
            'monetary_account_iban' => $defaultAccount->monetary_account_iban,
            'monetary_account_name' => $defaultAccount->monetary_account_name,
        ]);

        $transactionsBulk->log(self::EVENT_CREATED, $transactionsBulk->getLogModels($employee));

        $query->take($perBulk)->update([
            'voucher_transaction_bulk_id' => $transactionsBulk->id,
        ]);

        if ($sponsor->bank_connection_active->bank->isBunq()) {
            $transactionsBulk->submitBulkToBunq($employee);
        }

        $bulksList[] = $transactionsBulk->id;

        if (static::getNextBulkTransactionsForSponsor($sponsor)->exists()) {
            return static::buildBulksForOrganization($sponsor, $employee, $bulksList);
        }

        return $bulksList;
    }

    /**
     * @param Employee|null $employee
     * @return $this
     * @throws Throwable
     */
    public function resetBulk(?Employee $employee): self
    {
        $this->updateModel([
            'state' => static::STATE_PENDING,
        ]);

        $this->log(self::EVENT_RESET, $this->getLogModels($employee));
        $this->submitBulkToBunq($employee);

        return $this;
    }

    /**
     * @param Employee|null $employee
     * @param array $extraModels
     * @return array
     */
    protected function getLogModels(?Employee $employee = null, array $extraModels = []): array
    {
        return array_merge([
            'sponsor' => $this->bank_connection->organization,
            'employee' => $employee,
            'bank_connection' => $this->bank_connection,
            'voucher_transaction_bulk' => $this,
        ], $extraModels);
    }

    /**
     * @return bool
     * @throws Throwable
     */
    public function updatePaymentStatus(): bool
    {
        $payment = $this->fetchPayment();

        if (!$payment) {
            return false;
        }

        $this->update([
            'state_fetched_times'   => $this->state_fetched_times + 1,
            'state_fetched_at'      => now(),
        ]);

        switch (strtolower($payment->getStatus())) {
            case static::STATE_REJECTED: $this->setRejected(); break;
            case static::STATE_ACCEPTED: {
                if ($this->bank_connection->bank->isBunq()) {
                    $this->setAccepted($payment);
                }

                if ($this->bank_connection->bank->isBNG()) {
                    $this->setAcceptedBNG($payment);
                }
            } break;
        }

        return true;
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
            "/organizations/%d/transaction-bulks/%d",
            $this->bank_connection->organization_id,
            $this->id
        ), array_filter(compact('success', 'error')));
    }

    /**
     * @param Request $request
     * @return Builder
     */
    public static function search(Request $request): Builder
    {
        /** @var Builder $query */
        $query = self::query();

        if ($request->has('from') && $from = $request->input('from')) {
            $query->where('created_at','>=',
                (Carbon::createFromFormat('Y-m-d', $from))->startOfDay()->format('Y-m-d H:i:s')
            );
        }

        if ($request->has('to') && $to = $request->input('to')) {
            $query->where('created_at','<=',
                (Carbon::createFromFormat('Y-m-d', $to))->endOfDay()->format('Y-m-d H:i:s')
            );
        }

        if ($request->has('state') && $state = $request->input('state')) {
            $query->where('state', $state);
        }

        if ($quantity_min = $request->input('quantity_min')) {
            $query->has('voucher_transactions', '>=', $quantity_min);
        }

        if ($quantity_max = $request->input('quantity_max')) {
            $query->has('voucher_transactions', '<=', $quantity_max);
        }

        if ($amount_min = $request->input('amount_min')) {
            $query->whereHas('voucher_transactions', function (Builder $builder) use ($amount_min) {
                $builder->select(\DB::raw('SUM(amount) as total_amount'))
                    ->having('total_amount', '>=', $amount_min);
            });
        }

        if ($amount_max = $request->input('amount_max')) {
            $query->whereHas('voucher_transactions', function (Builder $builder) use ($amount_max) {
                $builder->select(\DB::raw('SUM(amount) as total_amount'))
                    ->having('total_amount', '<=', $amount_max);
            });
        }

        return $query;
    }

    /**
     * @param Request $request
     * @param Organization $organization
     * @return Builder
     */
    public static function searchSponsor(
        Request $request,
        Organization $organization
    ): Builder {
        return self::search($request)->whereHas('bank_connection', function (Builder $builder) use ($organization) {
            $builder->where('bank_connections.organization_id', $organization->id);
        });
    }

    /**
     * @param Builder $builder
     * @param array $fields
     * @return Builder[]|Collection|\Illuminate\Support\Collection
     */
    private static function exportListTransform(Builder $builder, array $fields)
    {
        return $builder->get()->map(static function(
            VoucherTransactionBulk $transactionBulk
        ) use ($fields) {
            return array_only([
                "id" => $transactionBulk->id,
                "quantity" => $transactionBulk->voucher_transactions_count,
                "amount" => currency_format(
                    $transactionBulk->voucher_transactions->sum('amount')
                ),
                "bank_name" => $transactionBulk->bank_connection->bank->name,
                "date_transaction" => format_datetime_locale($transactionBulk->created_at),
                "state" => $transactionBulk->state,
            ], $fields);
        })->values();
    }

    /**
     * @param VoucherTransactionBulk $transactionBulk
     * @param array $fields
     * @return Collection|\Illuminate\Support\Collection
     */
    private static function exportTransform(VoucherTransactionBulk $transactionBulk, array $fields)
    {
        return $transactionBulk->voucher_transactions->map(static function(
            VoucherTransaction $transaction
        ) use ($fields) {
            return array_only([
                "id" => $transaction->id,
                "amount" => currency_format($transaction->amount),
                "date_transaction" => format_datetime_locale($transaction->created_at),
                "fund_name" => $transaction->voucher->fund->name,
                "provider"  => Organization::find($transaction->organization_id)->name,
                "state" => $transaction->state,
            ], $fields);
        })->values();
    }

    /**
     * @param Request $request
     * @param Organization $organization
     * @param array $fields
     * @return Builder[]|Collection|\Illuminate\Support\Collection
     */
    public static function exportListSponsor(
        Request $request,
        Organization $organization,
        array $fields
    ) {
        return self::exportListTransform(VoucherTransactionBulkQuery::order(
            self::searchSponsor($request, $organization),
            $request->get('order_by'),
            $request->get('order_dir')
        ), $fields);
    }

    /**
     * @param Request $request
     * @param VoucherTransactionBulk $transactionBulk
     * @param array $fields
     * @return Collection|\Illuminate\Support\Collection
     */
    public static function exportSponsor(
        Request $request,
        VoucherTransactionBulk $transactionBulk,
        array $fields
    ) {
        return self::exportTransform($transactionBulk, $fields);
    }
}
