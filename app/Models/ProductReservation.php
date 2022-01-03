<?php

namespace App\Models;

use App\Events\ProductReservations\ProductReservationAccepted;
use App\Events\ProductReservations\ProductReservationCanceled;
use App\Events\ProductReservations\ProductReservationRejected;
use App\Events\VoucherTransactions\VoucherTransactionCreated;
use App\Services\EventLogService\Traits\HasLogs;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * App\Models\ProductReservation
 *
 * @property int $id
 * @property int $product_id
 * @property int $voucher_id
 * @property int|null $employee_id
 * @property int|null $voucher_transaction_id
 * @property int|null $fund_provider_product_id
 * @property string $amount
 * @property string $price
 * @property string $price_discount
 * @property string $code
 * @property string $price_type
 * @property string $state
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $user_note
 * @property string|null $note
 * @property \Illuminate\Support\Carbon|null $accepted_at
 * @property \Illuminate\Support\Carbon|null $canceled_at
 * @property \Illuminate\Support\Carbon|null $rejected_at
 * @property \Illuminate\Support\Carbon|null $expire_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\FundProviderProduct|null $fund_provider_product
 * @property-read string $state_locale
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\EventLogService\Models\EventLog[] $logs
 * @property-read int|null $logs_count
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\Voucher|null $product_voucher
 * @property-read \App\Models\Voucher $voucher
 * @property-read \App\Models\VoucherTransaction|null $voucher_transaction
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation newQuery()
 * @method static \Illuminate\Database\Query\Builder|ProductReservation onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereAcceptedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereCanceledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereExpireAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereFundProviderProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation wherePriceDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation wherePriceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereRejectedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereUserNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereVoucherId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereVoucherTransactionId($value)
 * @method static \Illuminate\Database\Query\Builder|ProductReservation withTrashed()
 * @method static \Illuminate\Database\Query\Builder|ProductReservation withoutTrashed()
 * @mixin \Eloquent
 */
class ProductReservation extends Model
{
    use SoftDeletes, HasLogs;

    public const EVENT_CREATED = 'created';
    public const EVENT_REJECTED = 'rejected';
    public const EVENT_ACCEPTED = 'accepted';
    public const EVENT_CANCELED = 'canceled';

    public const STATE_PENDING = 'pending';
    public const STATE_ACCEPTED = 'accepted';
    public const STATE_REJECTED = 'rejected';
    public const STATE_CANCELED = 'canceled';

    /**
     * The events of the product reservation.
     */
    public const EVENTS = [
        self::EVENT_CREATED,
        self::EVENT_REJECTED,
        self::EVENT_ACCEPTED,
        self::EVENT_CANCELED,
    ];

    /**
     * The states of the product reservation.
     */
    public const STATES = [
        self::STATE_PENDING,
        self::STATE_ACCEPTED,
        self::STATE_REJECTED,
        self::STATE_CANCELED,
    ];

    /**
     * The number of days the transaction payout has to be delayed.
     */
    const TRANSACTION_DELAY = 14;

    /**
     * @var string[]
     */
    protected $fillable = [
        'product_id', 'voucher_id', 'voucher_transaction_id', 'fund_provider_product_id',
        'amount', 'state', 'accepted_at', 'rejected_at', 'canceled_at', 'expire_at',
        'price', 'price_type', 'price_discount', 'code', 'note', 'employee_id',
        'first_name', 'last_name', 'user_note',
    ];

    /**
     * @var string[]
     */
    protected $dates = [
        'accepted_at', 'rejected_at', 'canceled_at', 'expire_at',
    ];

    /**
     * @throws \Exception
     */
    public static function makeCode(): int
    {
        do {
            $code = random_int(11111111, 99999999);
        } while(self::query()->where(compact('code'))->exists());

        return $code;
    }

    /**
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        /** @var BelongsTo|SoftDeletes $relationShip */
        $relationShip = $this->belongsTo(Product::class);

        return $relationShip->withTrashed();
    }

    /**
     * @return BelongsTo
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    /**
     * @return BelongsTo
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo
     */
    public function voucher_transaction(): BelongsTo
    {
        return $this->belongsTo(VoucherTransaction::class);
    }

    /**
     * @return HasOne
     * @noinspection PhpUnused
     */
    public function product_voucher(): HasOne
    {
        return $this->hasOne(Voucher::class);
    }

    /**
     * @return BelongsTo
     */
    public function fund_provider_product(): BelongsTo
    {
        return $this->belongsTo(FundProviderProduct::class);
    }

    /**
     * @return string
     */
    public function getStateLocaleAttribute(): string
    {
        return trans('states/product_reservations.' . $this->state);
    }

    /**
     * @return bool
     */
    public function hasExpired(): bool
    {
        return $this->isPending() && !$this->expire_at->endOfDay()->isFuture();
    }

    /**
     * @param Employee|null $employee
     * @return $this
     */
    public function acceptProvider(?Employee $employee = null): self
    {
        return $this->setAccepted($this->makeTransaction($employee));
    }

    /**
     * @param Employee|null $employee
     * @return VoucherTransaction
     */
    public function makeTransaction(?Employee $employee = null): VoucherTransaction
    {
        $fund_end_date = $this->voucher->fund->end_date;

        /** @var VoucherTransaction $transaction */
        $transaction = $this->product_voucher->transactions()->create(array_merge([
            'state' => 'pending',
            'amount' => $this->amount,
            'address' => token_generator()->address(),
            'product_id' => $this->product_id,
            'transfer_at' => $fund_end_date->isPast() ? $fund_end_date : now()->closest(
                now()->addDays(self::TRANSACTION_DELAY),
                $fund_end_date
            ),
            'employee_id' => $employee ? $employee->id : null,
            'fund_provider_product_id' => $this->fund_provider_product_id ?? null,
            'organization_id' => $this->product->organization_id,
        ]));

        VoucherTransactionCreated::dispatch($transaction);

        return $transaction;
    }

    /**
     * @param VoucherTransaction $transaction
     * @return $this
     */
    public function setAccepted(VoucherTransaction $transaction): self
    {
        $this->update([
            'state' => self::STATE_ACCEPTED,
            'accepted_at' => now(),
            'voucher_transaction_id' => $transaction->id,
        ]);

        ProductReservationAccepted::dispatch($this);

        return $this;
    }

    /**
     * @param Employee|null $employee
     * @return $this
     * @throws \Exception
     */
    public function rejectOrCancelProvider(?Employee $employee = null): self
    {
        $isAccepted = $this->isAccepted();

        $this->updateModel($isAccepted ? [
            'state' => self::STATE_CANCELED,
            'canceled_at' => now(),
            'employee_id' => $employee ? $employee->id : null,
        ] : [
            'state' => self::STATE_REJECTED,
            'rejected_at' => now(),
            'employee_id' => $employee ? $employee->id : null,
        ]);

        if ($this->voucher_transaction && $this->voucher_transaction->isCancelable()) {
            $this->voucher_transaction->cancelPending();
        }

        if ($isAccepted) {
            ProductReservationCanceled::dispatch($this);
        } else {
            ProductReservationRejected::dispatch($this);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->state === self::STATE_PENDING;
    }

    /**
     * @return bool
     */
    public function isAccepted(): bool
    {
        return $this->state === self::STATE_ACCEPTED;
    }

    /**
     * @return bool
     */
    public function isCancelableByProvider(): bool
    {
        return $this->isPending() || (
            $this->isAccepted() &&
            $this->voucher_transaction &&
            $this->voucher_transaction->isCancelable());
    }

    /**
     * @return Voucher
     */
    public function makeVoucher(): Voucher
    {
        return $this->voucher->buyProductVoucher($this->product, $this);
    }

    /**
     * @return bool|null
     * @throws \Exception
     */
    public function cancelByClient(): ?bool
    {
        if ($this->product_voucher) {
            $this->product_voucher->delete();
        }

        return $this->delete();
    }

    /**
     * @param string|null $identity_address
     * @param string|null $note
     * @return VoucherTransaction
     */
    public function acceptByApp(?string $identity_address, ?string $note = null): VoucherTransaction
    {
        $voucher = $this->product_voucher;
        $needsReview = $voucher->needsTransactionReview();

        $employee = $voucher->product->organization->findEmployee($identity_address);
        $transaction = $voucher->product_reservation->acceptProvider($employee)->voucher_transaction;
        $transaction->wasRecentlyCreated = true;

        $needsReview && $transaction->setForReview();
        $note && $transaction->addNote('provider', $note);

        return $transaction;
    }
}
