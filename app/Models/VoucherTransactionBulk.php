<?php

namespace App\Models;

use bunq\Model\Generated\Endpoint\DraftPayment;
use bunq\Model\Generated\Endpoint\Payment;
use bunq\Model\Generated\Endpoint\PaymentBatch;
use bunq\Model\Generated\Object\Amount;
use bunq\Model\Generated\Object\DraftPaymentEntry;
use bunq\Model\Generated\Object\Pointer;
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
        'bank_connection_id', 'state', 'state_fetched_times', 'state_fetched_at', 'payment_id',
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
     * @return string
     */
    public function fetchStatus(): string
    {
        return $this->fetchPayment()->getStatus();
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
                $filter = function(Payment $payment) use ($transaction) {
                    return strtolower($payment->getDescription()) == strtolower($transaction->payment_description);
                };

                $payments = $draftPayment->getObject()->getReferencedObject();

                if ($payments instanceof PaymentBatch) {
                    $payments = $payments->getPayments()->getPayment() ?: [];
                } else {
                    $payments = [$payments];
                }

                /** @var Payment|null $payment */
                $payment = array_filter(array_filter($payments), $filter)[0] ?? null;

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
}
