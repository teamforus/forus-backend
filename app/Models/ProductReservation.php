<?php

namespace App\Models;

use App\Events\ProductReservations\ProductReservationAccepted;
use App\Events\ProductReservations\ProductReservationCanceled;
use App\Events\ProductReservations\ProductReservationPending;
use App\Events\ProductReservations\ProductReservationRejected;
use App\Events\ReservationExtraPayments\ReservationExtraPaymentCreated;
use App\Events\VoucherTransactions\VoucherTransactionCreated;
use App\Services\EventLogService\Traits\HasLogs;
use App\Services\MollieService\Exceptions\MollieException;
use App\Services\MollieService\MollieService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mollie\Api\Resources\Payment;

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
 * @property string $amount_extra
 * @property string $price
 * @property string $price_discount
 * @property string $code
 * @property string $price_type
 * @property string|null $state
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $phone
 * @property string $address
 * @property string|null $street
 * @property string|null $house_nr
 * @property string|null $house_nr_addition
 * @property string|null $postal_code
 * @property string|null $city
 * @property \Illuminate\Support\Carbon|null $birth_date
 * @property string|null $user_note
 * @property string|null $note
 * @property bool $archived
 * @property \Illuminate\Support\Carbon|null $accepted_at
 * @property \Illuminate\Support\Carbon|null $canceled_at
 * @property \Illuminate\Support\Carbon|null $rejected_at
 * @property \Illuminate\Support\Carbon|null $expire_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductReservationFieldValue[] $custom_fields
 * @property-read int|null $custom_fields_count
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\ReservationExtraPayment|null $extra_payment
 * @property-read \App\Models\FundProviderProduct|null $fund_provider_product
 * @property-read string $state_locale
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\EventLogService\Models\EventLog[] $logs
 * @property-read int|null $logs_count
 * @property-read \App\Models\ReservationExtraPayment|null $mollie_extra_payment
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\Voucher|null $product_voucher
 * @property-read \App\Models\Voucher $voucher
 * @property-read \App\Models\VoucherTransaction|null $voucher_transaction
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereAcceptedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereArchived($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereBirthDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereCanceledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereExpireAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereExtraAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereFundProviderProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereHouseNr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereHouseNrAddition($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation wherePostalCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation wherePriceDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation wherePriceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereRejectedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereStreet($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereUserNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereVoucherId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation whereVoucherTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservation withoutTrashed()
 * @mixin \Eloquent
 */
class ProductReservation extends BaseModel
{
    use SoftDeletes, HasLogs;

    public const EVENT_CREATED = 'created';
    public const EVENT_REJECTED = 'rejected';
    public const EVENT_ACCEPTED = 'accepted';
    public const EVENT_PENDING = 'pending';
    public const EVENT_CANCELED_BY_PROVIDER = 'canceled';
    public const EVENT_CANCELED_BY_CLIENT = 'canceled_by_client';
    public const EVENT_CANCELED_PAYMENT_FAILED = 'canceled_payment_failed';
    public const EVENT_CANCELED_PAYMENT_EXPIRED = 'canceled_payment_expired';
    public const EVENT_CANCELED_PAYMENT_CANCELED = 'canceled_payment_canceled';

    public const STATE_WAITING = 'waiting';
    public const STATE_PENDING = 'pending';
    public const STATE_ACCEPTED = 'accepted';
    public const STATE_REJECTED = 'rejected';
    public const STATE_CANCELED_BY_PROVIDER = 'canceled';
    public const STATE_CANCELED_BY_CLIENT = 'canceled_by_client';
    public const STATE_CANCELED_PAYMENT_FAILED = 'canceled_payment_failed';
    public const STATE_CANCELED_PAYMENT_EXPIRED = 'canceled_payment_expired';
    public const STATE_CANCELED_PAYMENT_CANCELED = 'canceled_payment_canceled';
    public const EVENT_ARCHIVED = 'archived';
    public const EVENT_UNARCHIVED = 'unarchived';

    /**
     * The events of the product reservation.
     */
    public const EVENTS = [
        self::EVENT_CREATED,
        self::EVENT_REJECTED,
        self::EVENT_ACCEPTED,
        self::EVENT_PENDING,
        self::EVENT_CANCELED_BY_CLIENT,
        self::EVENT_CANCELED_BY_PROVIDER,
        self::EVENT_CANCELED_PAYMENT_FAILED,
        self::EVENT_CANCELED_PAYMENT_EXPIRED,
        self::EVENT_CANCELED_PAYMENT_CANCELED,
    ];

    /**
     * The states of the product reservation.
     */
    public const STATES = [
        self::STATE_WAITING,
        self::STATE_PENDING,
        self::STATE_ACCEPTED,
        self::STATE_REJECTED,
        self::STATE_CANCELED_BY_CLIENT,
        self::STATE_CANCELED_BY_PROVIDER,
        self::STATE_CANCELED_PAYMENT_FAILED,
        self::STATE_CANCELED_PAYMENT_EXPIRED,
        self::STATE_CANCELED_PAYMENT_CANCELED,
    ];

    /**
     * The states of a canceled product reservation.
     */
    public const STATES_CANCELED = [
        self::STATE_CANCELED_BY_CLIENT,
        self::STATE_CANCELED_BY_PROVIDER,
        self::STATE_CANCELED_PAYMENT_FAILED,
        self::STATE_CANCELED_PAYMENT_EXPIRED,
        self::STATE_CANCELED_PAYMENT_CANCELED,
    ];

    /**
     * The number of days the transaction payout has to be delayed.
     */
    public const TRANSACTION_DELAY = 14;

    /**
     * @var string[]
     */
    protected $fillable = [
        'product_id', 'voucher_id', 'voucher_transaction_id', 'fund_provider_product_id',
        'amount', 'state', 'accepted_at', 'rejected_at', 'canceled_at', 'expire_at',
        'price', 'price_type', 'price_discount', 'code', 'note', 'employee_id',
        'first_name', 'last_name', 'user_note', 'phone', 'address', 'birth_date', 'archived',
        'street', 'house_nr', 'house_nr_addition', 'postal_code', 'city', 'amount_extra',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'archived' => 'boolean',
    ];

    /**
     * @var string[]
     */
    protected $dates = [
        'accepted_at', 'rejected_at', 'canceled_at', 'expire_at', 'birth_date',
    ];

    /**
     * @throws \Exception
     */
    public static function makeCode(): int
    {
        do {
            $code = random_int(11111111, 99999999);
        } while (self::query()->where(compact('code'))->exists());

        return $code;
    }

    /**
     * @param $value
     * @return string
     * @noinspection PhpUnused
     */
    public function getAddressAttribute($value): string
    {
        return $value ?: sprintf("%s %s", $this->street ?: '', implode(', ', array_filter([
            $this->house_nr, $this->house_nr_addition, $this->postal_code, $this->city,
        ])));
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
     * @noinspection PhpUnused
     */
    public function fund_provider_product(): BelongsTo
    {
        return $this->belongsTo(FundProviderProduct::class);
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function custom_fields(): HasMany
    {
        return $this->hasMany(ProductReservationFieldValue::class);
    }

    /**
     * @return HasOne
     * @noinspection PhpUnused
     */
    public function extra_payment(): HasOne
    {
        return $this->hasOne(ReservationExtraPayment::class);
    }

    /**
     * @return string
     * @noinspection PhpUnused
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
     * @return bool
     */
    public function isCanceled(): bool
    {
        return in_array($this->state, self::STATES_CANCELED, true);
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function isCanceledByClient(): bool
    {
        return $this->state === self::STATE_CANCELED_BY_CLIENT;
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function isCanceledByProvider(): bool
    {
        return $this->state === self::STATE_CANCELED_BY_PROVIDER;
    }

    /**
     * @return bool
     */
    public function isArchived(): bool
    {
        return $this->archived;
    }

    /**
     * @return bool
     */
    public function isArchivable(): bool
    {
        return !$this->archived && ($this->isAccepted() || $this->isCanceled());
    }

    /**
     * @param Employee|null $employee
     * @return $this
     * @throws \Throwable
     */
    public function acceptProvider(?Employee $employee = null): self
    {
        DB::transaction(function() use ($employee) {
            return $this->setAccepted($this->makeTransaction($employee));
        });

        return $this;
    }

    /**
     * @return $this
     */
    public function archive(Employee $employee): self
    {
        $this->updateModel([
            'archived' => true,
        ])->log(self::EVENT_ARCHIVED, $this->getLogModels($employee));

        return $this;
    }

    /**
     * @return $this
     */
    public function unArchive(Employee $employee): self
    {
        $this->updateModel([
            'archived' => false,
        ])->log(self::EVENT_UNARCHIVED, $this->getLogModels($employee));

        return $this;
    }

    /**
     * @param Employee|null $employee
     * @return VoucherTransaction
     */
    public function makeTransaction(?Employee $employee = null): VoucherTransaction
    {
        $fund_end_date = $this->voucher->fund->end_date;
        $transfer_at = $fund_end_date->isPast() ? $fund_end_date : now()->closest(
            now()->addDays(self::TRANSACTION_DELAY),
            $fund_end_date
        );

        $transaction = $this->product_voucher->makeTransaction(array_merge([
            'amount' => $this->amount,
            'product_id' => $this->product_id,
            'transfer_at' => $transfer_at,
            'employee_id' => $employee?->id,
            'target' => VoucherTransaction::TARGET_PROVIDER,
            'organization_id' => $this->product->organization_id,
            'fund_provider_product_id' => $this->fund_provider_product_id ?? null,
        ]));

        Event::dispatch(new VoucherTransactionCreated($transaction));

        return $transaction;
    }

    /**
     * @return $this
     */
    public function setPending(): self
    {
        $this->update([
            'state' => self::STATE_PENDING,
        ]);

        Event::dispatch(new ProductReservationPending($this));

        return $this;
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

        Event::dispatch(new ProductReservationAccepted($this));

        return $this;
    }

    /**
     * @param Employee|null $employee
     * @return $this
     * @throws \Throwable
     */
    public function rejectOrCancelProvider(?Employee $employee = null): self
    {
        DB::transaction(function() use ($employee) {
            $isRefund = $this->isAccepted();

            $this->update($isRefund ? [
                'state' => self::STATE_CANCELED_BY_PROVIDER,
                'canceled_at' => now(),
                'employee_id' => $employee?->id,
            ] : [
                'state' => self::STATE_REJECTED,
                'rejected_at' => now(),
                'employee_id' => $employee?->id,
            ]);

            if ($this->voucher_transaction && $this->voucher_transaction->isCancelable()) {
                $this->voucher_transaction->cancelPending();
            }

            if ($isRefund) {
                Event::dispatch(new ProductReservationCanceled($this));
            } else {
                Event::dispatch(new ProductReservationRejected($this));
            }
        });

        return $this;
    }

    /**
     * @return bool
     */
    public function isWaiting(): bool
    {
        return $this->state === self::STATE_WAITING;
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
        if ($this->isCancelableByRequester()) {
            return true;
        }

        if ($this->isWaiting()) {
            return $this->extra_payment?->isExpired();
        }

        if ($this->isPending()) {
            return !$this->extra_payment || $this->extra_payment?->isFullyRefunded();
        }

        if ($this->isAccepted()) {
            return
                $this->voucher_transaction?->isCancelable() &&
                !$this->extra_payment || $this->extra_payment?->isFullyRefunded();
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isCancelableByRequester(): bool
    {
        if (!$this->isWaiting() && !$this->isPending()) {
            return false;
        }

        return
            !$this->extra_payment ||
            $this->extra_payment->isExpired() ||
            $this->extra_payment->isCanceled() ||
            $this->extra_payment->isCancelable();
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
     * @throws \Throwable
     */
    public function cancelByClient(): ?bool
    {
        DB::transaction(function() {
            if ($this->product_voucher) {
                $this->product_voucher->delete();
            }

            $this->update([
                'state' => self::STATE_CANCELED_BY_CLIENT,
                'canceled_at' => now(),
            ]);

            Event::dispatch(new ProductReservationCanceled($this));
        });

        return true;
    }

    /**
     * @param string $state
     * @return bool
     * @throws \Throwable
     */
    public function cancelByState(string $state): bool
    {
        DB::transaction(function() use ($state) {
            $this->product_voucher?->delete();

            $this->update([
                'state' => $state,
                'canceled_at' => now(),
            ]);

            ProductReservationCanceled::dispatch($this);
        });

        return true;
    }

    /**
     * @param string|null $identity_address
     * @param string|null $note
     * @return VoucherTransaction
     * @throws \Throwable
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

    /**
     * @param Employee|null $employee
     * @param array $extraModels
     * @return array
     */
    protected function getLogModels(?Employee $employee = null, array $extraModels = []): array
    {
        return array_merge([
            'provider' => $this->product->organization,
            'employee' => $employee,
            'product_reservation' => $this,
        ], $extraModels);
    }

    /**
     * @param Implementation $implementation
     * @return Payment|null
     */
    public function createExtraPayment(Implementation $implementation): ?Payment
    {
        return $this->createExtraPaymentMollie($implementation, $this->amount_extra);
    }

    /**
     * @param Implementation $implementation
     * @param float $amount
     * @param string $currency
     * @return Payment|null
     */
    protected function createExtraPaymentMollie(
        Implementation $implementation,
        float $amount,
        string $currency = 'EUR',
    ): ?Payment {
        if (!$this->product->organization?->mollie_connection?->onboardingComplete()) {
            return null;
        }

        $payment = $this->product->organization->mollie_connection->createPayment([
            'method' => MollieService::PAYMENT_METHOD_IDEAL,
            'amount' => $amount,
            'currency' => $currency,
            'description' => trans('extra-payments.payment.description'),
            'redirect_url' => $implementation->urlWebshop("/reservations/$this->id?checkout=1"),
            'cancel_url' => $implementation->urlWebshop("/reservations/$this->id"),
        ]);

        if (!$payment) {
            return null;
        }

        $expireAt = $payment->expiresAt ? Carbon::parse($payment->expiresAt, 'UTC') : null;

        $extraPayment = $this->extra_payment()->create([
            'type' => ReservationExtraPayment::TYPE_MOLLIE,
            'state' => $payment->status,
            'amount' => $amount,
            'currency' => $currency,
            'method' => $payment->method,
            'expires_at' => $expireAt?->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
            'payment_id' => $payment->id,
        ]);

        Event::dispatch(new ReservationExtraPaymentCreated($extraPayment));

        return $payment;
    }

    /**
     * @param Employee|null $employee
     * @return ReservationExtraPayment|null
     * @throws MollieException|\Throwable
     */
    public function fetchExtraPayment(?Employee $employee): ?ReservationExtraPayment
    {
        if ($this->extra_payment?->payment_id && $this->extra_payment?->isMollieType()) {
            return $this->extra_payment->fetchAndUpdateMolliePayment($employee);
        }

        return null;
    }

    /**
     * @param Employee|null $employee
     * @return ReservationExtraPaymentRefund|null
     * @throws MollieException|\Throwable
     */
    public function refundExtraPayment(?Employee $employee): ?ReservationExtraPaymentRefund
    {
        if ($this->extra_payment?->payment_id && $this->extra_payment->isMollieType()) {
            return $this->extra_payment->createMollieRefund($employee);
        }

        return null;
    }

    /**
     * @return int|null
     */
    public function expiresIn(): ?int
    {
        if ($this->created_at && $this->isWaiting()) {
            $timeOffset = Config::get('forus.reservations.extra_payment_waiting_time');
            $expireAt = $this->created_at->clone()->addMinutes($timeOffset);
            $expiresIn = now()->diffInSeconds($expireAt, false);

            return max(min($expiresIn, $this->extra_payment->expiresIn() ?: 0), 0) ?: null;
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isAcceptable(): bool
    {
        return
            $this->isPending() &&
            !$this->hasExpired() &&
            !$this->product->trashed() &&
            (!$this->extra_payment || $this->extra_payment->isPaid()) &&
            (!$this->extra_payment || $this->extra_payment->refunds_active->isEmpty());
    }
}
