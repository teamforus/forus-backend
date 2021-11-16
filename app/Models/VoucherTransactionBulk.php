<?php

namespace App\Models;

use App\Scopes\Builders\VoucherTransactionQuery;
use bunq\Model\Generated\Endpoint\DraftPayment;
use bunq\Model\Generated\Endpoint\Payment;
use bunq\Model\Generated\Endpoint\PaymentBatch;
use bunq\Model\Generated\Object\Amount;
use bunq\Model\Generated\Object\DraftPaymentEntry;
use bunq\Model\Generated\Object\Pointer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * App\Models\VoucherTransactionBulk
 *
 * @property int $id
 * @property int|null $bank_connection_id
 * @property int|null $payment_id
 * @property string $state
 * @property int $state_fetched_times
 * @property string|null $state_fetched_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\BankConnection|null $bank_connection
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\VoucherTransaction[] $voucher_transactions
 * @property-read int|null $voucher_transactions_count
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherTransactionBulk newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherTransactionBulk newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherTransactionBulk query()
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherTransactionBulk whereBankConnectionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherTransactionBulk whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherTransactionBulk whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherTransactionBulk wherePaymentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherTransactionBulk whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherTransactionBulk whereStateFetchedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherTransactionBulk whereStateFetchedTimes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherTransactionBulk whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class VoucherTransactionBulk extends Model
{
    public const STATE_PENDING = 'pending';
    public const STATE_ACCEPTED = 'accepted';
    public const STATE_REJECTED = 'rejected';

    /**
     * @var string[]
     */
    protected $fillable = [
        'bank_connection_id', 'state', 'state_fetched_times', 'state_fetched_at',
        'payment_id', 'accepted_manually',
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
        return DraftPayment::get($this->payment_id)->getValue();
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

            foreach ($this->voucher_transactions as $transaction) {
                $payment = $draftPayment ? $this->findPaymentFromDraftPayment(
                    $transaction, $draftPayment
                ) : null;

                $transaction->forceFill([
                    'state'             => VoucherTransaction::STATE_SUCCESS,
                    'payment_id'        => $payment ? $payment->getId() : null,
                    'iban_from'         => $this->bank_connection->monetary_account_iban,
                    'iban_to'           => $transaction->provider->iban,
                    'payment_time'      => now(),
                ])->save();

                $transaction->sendPushBunqTransactionSuccess();
            }
        });

        sleep(1);

        return $this->fresh();
    }

    /**
     * @return $this
     * @throws Throwable
     */
    public function submitBulk(): self
    {
        return DB::transaction(function() {
            $this->bank_connection->useContext();

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

            $monetaryAccountId = $this->bank_connection->monetary_account_id;
            $payment = DraftPayment::create($transactions, 1, $monetaryAccountId);

            return tap($this)->update([
                'payment_id' => $payment->getValue(),
            ]);
        });
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
        })->whereHas('bank_connection_active')->get();

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
     * @param array $previousBulks
     * @param int $perBulk
     * @return int[]
     */
    public static function buildBulksForOrganization(
        Organization $sponsor,
        array $previousBulks = [],
        int $perBulk = 100
    ): array {
        $query = static::getNextBulkTransactionsForSponsor($sponsor);

        if ((clone($query))->doesntExist()) {
            return $previousBulks;
        }

        /** @var VoucherTransactionBulk $transactionsBulk */
        $transactionsBulk = $sponsor->bank_connection_active->voucher_transaction_bulks()->create([
            'state' => VoucherTransactionBulk::STATE_PENDING,
        ]);

        $query->take($perBulk)->update([
            'voucher_transaction_bulk_id' => $transactionsBulk->id,
        ]);

        try {
            $transactionsBulk->submitBulk();
            // This endpoint is throttled by bunq: You can do a maximum of 3 calls per 3 second to this endpoint.
            sleep(2);
        } catch (Throwable $e) {
            logger()->error($e->getMessage() . "\n" . $e->getTraceAsString());
        }

        $bulksList = array_merge($previousBulks, (array) $transactionsBulk->id);

        if (static::getNextBulkTransactionsForSponsor($sponsor)->exists()) {
            return static::buildBulksForOrganization($sponsor, $bulksList, $perBulk);
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
        return tap($this)->update([
            'state' => static::STATE_PENDING,
        ])->submitBulk();
    }
}
