<?php

namespace App\Models;

use App\Events\VoucherTransactions\VoucherTransactionBunqSuccess;
use App\Scopes\Builders\VoucherTransactionQuery;
use App\Services\EventLogService\Traits\HasLogs;
use bunq\Model\Generated\Endpoint\DraftPayment;
use bunq\Model\Generated\Endpoint\Payment;
use bunq\Model\Generated\Endpoint\PaymentBatch;
use bunq\Model\Generated\Object\Amount;
use bunq\Model\Generated\Object\DraftPaymentEntry;
use bunq\Model\Generated\Object\Pointer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Exception;
use Throwable;

/**
 * App\Models\VoucherTransactionBulk
 *
 * @property int $id
 * @property int|null $bank_connection_id
 * @property int|null $payment_id
 * @property string $monetary_account_id
 * @property string $monetary_account_iban
 * @property string $state
 * @property int $accepted_manually
 * @property int $state_fetched_times
 * @property string|null $state_fetched_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\BankConnection|null $bank_connection
 * @property-read string $state_locale
 * @property-read Collection|\App\Services\EventLogService\Models\EventLog[] $logs
 * @property-read int|null $logs_count
 * @property-read Collection|\App\Models\VoucherTransaction[] $voucher_transactions
 * @property-read int|null $voucher_transactions_count
 * @method static Builder|VoucherTransactionBulk newModelQuery()
 * @method static Builder|VoucherTransactionBulk newQuery()
 * @method static Builder|VoucherTransactionBulk query()
 * @method static Builder|VoucherTransactionBulk whereAcceptedManually($value)
 * @method static Builder|VoucherTransactionBulk whereBankConnectionId($value)
 * @method static Builder|VoucherTransactionBulk whereCreatedAt($value)
 * @method static Builder|VoucherTransactionBulk whereId($value)
 * @method static Builder|VoucherTransactionBulk whereMonetaryAccountIban($value)
 * @method static Builder|VoucherTransactionBulk whereMonetaryAccountId($value)
 * @method static Builder|VoucherTransactionBulk wherePaymentId($value)
 * @method static Builder|VoucherTransactionBulk whereState($value)
 * @method static Builder|VoucherTransactionBulk whereStateFetchedAt($value)
 * @method static Builder|VoucherTransactionBulk whereStateFetchedTimes($value)
 * @method static Builder|VoucherTransactionBulk whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class VoucherTransactionBulk extends Model
{
    use HasLogs;

    public const EVENT_RESET = 'reset';
    public const EVENT_CREATED = 'created';
    public const EVENT_SUBMITTED = 'submitted';
    public const EVENT_ACCEPTED = 'accepted';
    public const EVENT_REJECTED = 'rejected';

    public const EVENTS = [
        self::EVENT_RESET,
        self::EVENT_CREATED,
        self::EVENT_SUBMITTED,
        self::EVENT_ACCEPTED,
        self::EVENT_REJECTED,
    ];

    public const STATE_DRAFT = 'draft';
    public const STATE_PENDING = 'pending';
    public const STATE_ACCEPTED = 'accepted';
    public const STATE_REJECTED = 'rejected';

    public const STATES = [
        self::STATE_DRAFT,
        self::STATE_PENDING,
        self::STATE_ACCEPTED,
        self::STATE_REJECTED,
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'bank_connection_id', 'state', 'state_fetched_times', 'state_fetched_at',
        'payment_id', 'accepted_manually', 'monetary_account_id', 'monetary_account_iban',
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
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function voucher_transactions(): HasMany
    {
        return $this->hasMany(VoucherTransaction::class);
    }

    /**
     * @return string
     */
    public function getStateLocaleAttribute(): string
    {
        return [
            static::STATE_PENDING => 'In afwachting',
            static::STATE_ACCEPTED => 'Geaccepteerd',
            static::STATE_REJECTED => 'Geannuleerd',
            static::STATE_DRAFT => 'In voorbereiding',
        ][$this->state] ?? $this->state;
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
     * @return string
     */
    public function fetchStatus(): string
    {
        return $this->fetchPayment()->getStatus();
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
     * @return DraftPayment
     */
    public function fetchPayment(): DraftPayment
    {
        return DraftPayment::get($this->payment_id, $this->monetary_account_id)->getValue();
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
                    'iban_from'         => $this->monetary_account_iban,
                    'iban_to'           => $transaction->provider->iban,
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
     * @throws Throwable
     */
    public function submitBulk(?Employee $employee = null): self
    {
        return DB::transaction(function() use ($employee) {
            if (!$this->bank_connection->useContext()) {
                throw new Exception("Bank connection invalid.", 403);
            }

            $transactions = $this->voucher_transactions->map(function(VoucherTransaction $transaction) {
                $amount = number_format($transaction->amount, 2, '.', '');
                $paymentAmount = new Amount($amount, 'EUR');
                $paymentPointer = new Pointer('IBAN', $transaction->provider->iban, $transaction->provider->name);
                $paymentDescription = $transaction->makePaymentDescription();

                $transaction->update([
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
        ]);

        $transactionsBulk->log(self::EVENT_CREATED, $transactionsBulk->getLogModels($employee));

        $query->take($perBulk)->update([
            'voucher_transaction_bulk_id' => $transactionsBulk->id,
        ]);

        try {
            $transactionsBulk->submitBulk($employee);
            // This endpoint is throttled by bunq: You can do a maximum of 3 calls per 3 second to this endpoint.
            sleep(2);
        } catch (Throwable $e) {
            logger()->error($e->getMessage() . "\n" . $e->getTraceAsString());
        }

        $bulksList = array_merge($previousBulks, (array) $transactionsBulk->id);

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
        $this->submitBulk($employee);

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
}
