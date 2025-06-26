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
use App\Services\MollieService\Interfaces\MollieServiceInterface;
use App\Services\MollieService\Objects\Payment;
use Exception;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Throwable;

/**
 * App\Models\ProductReservation.
 *
 * @property int $id
 * @property int $product_id
 * @property int $voucher_id
 * @property int|null $employee_id
 * @property int|null $voucher_transaction_id
 * @property int|null $fund_provider_product_id
 * @property string $amount
 * @property string|null $amount_voucher
 * @property string $amount_extra
 * @property string $price
 * @property string $price_discount
 * @property string $code
 * @property string $price_type
 * @property string $state
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $phone
 * @property string $address
 * @property string|null $street
 * @property string|null $house_nr
 * @property string|null $house_nr_addition
 * @property string|null $postal_code
 * @property string|null $city
 * @property Carbon|null $birth_date
 * @property string|null $user_note
 * @property string|null $note
 * @property bool $archived
 * @property Carbon|null $accepted_at
 * @property Carbon|null $canceled_at
 * @property Carbon|null $rejected_at
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductReservationFieldValue[] $custom_fields
 * @property-read int|null $custom_fields_count
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\ReservationExtraPayment|null $extra_payment
 * @property-read \App\Models\FundProviderProduct|null $fund_provider_product
 * @property-read \App\Models\FundProviderProduct|null $fund_provider_product_with_trashed
 * @property-read Carbon $expire_at
 * @property-read string $state_locale
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\EventLogService\Models\EventLog[] $logs
 * @property-read int|null $logs_count
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\Voucher|null $product_voucher
 * @property-read \App\Models\Voucher $voucher
 * @property-read \App\Models\VoucherTransaction|null $voucher_transaction
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation whereAcceptedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation whereAmountExtra($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation whereAmountVoucher($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation whereArchived($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation whereBirthDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation whereCanceledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation whereFundProviderProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation whereHouseNr($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation whereHouseNrAddition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation wherePostalCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation wherePriceDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation wherePriceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation whereRejectedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation whereStreet($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation whereUserNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation whereVoucherId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation whereVoucherTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservation withoutTrashed()
 * @mixin \Eloquent
 */
class ProductReservation extends BaseModel
{
    use HasLogs;
    use SoftDeletes;

    public const string EVENT_CREATED = 'created';
    public const string EVENT_REJECTED = 'rejected';
    public const string EVENT_ACCEPTED = 'accepted';
    public const string EVENT_PENDING = 'pending';
    public const string EVENT_CANCELED_BY_PROVIDER = 'canceled';
    public const string EVENT_CANCELED_BY_CLIENT = 'canceled_by_client';
    public const string EVENT_CANCELED_BY_SPONSOR = 'canceled_by_sponsor';
    public const string EVENT_CANCELED_PAYMENT_FAILED = 'canceled_payment_failed';
    public const string EVENT_CANCELED_PAYMENT_EXPIRED = 'canceled_payment_expired';
    public const string EVENT_CANCELED_PAYMENT_CANCELED = 'canceled_payment_canceled';

    public const string STATE_WAITING = 'waiting';
    public const string STATE_PENDING = 'pending';
    public const string STATE_ACCEPTED = 'accepted';
    public const string STATE_REJECTED = 'rejected';
    public const string STATE_CANCELED_BY_PROVIDER = 'canceled';
    public const string STATE_CANCELED_BY_CLIENT = 'canceled_by_client';
    public const string STATE_CANCELED_BY_SPONSOR = 'canceled_by_sponsor';
    public const string STATE_CANCELED_PAYMENT_FAILED = 'canceled_payment_failed';
    public const string STATE_CANCELED_PAYMENT_EXPIRED = 'canceled_payment_expired';
    public const string STATE_CANCELED_PAYMENT_CANCELED = 'canceled_payment_canceled';
    public const string EVENT_ARCHIVED = 'archived';
    public const string EVENT_UNARCHIVED = 'unarchived';

    /**
     * The events of the product reservation.
     */
    public const array EVENTS = [
        self::EVENT_CREATED,
        self::EVENT_REJECTED,
        self::EVENT_ACCEPTED,
        self::EVENT_PENDING,
        self::EVENT_CANCELED_BY_CLIENT,
        self::EVENT_CANCELED_BY_SPONSOR,
        self::EVENT_CANCELED_BY_PROVIDER,
        self::EVENT_CANCELED_PAYMENT_FAILED,
        self::EVENT_CANCELED_PAYMENT_EXPIRED,
        self::EVENT_CANCELED_PAYMENT_CANCELED,
    ];

    /**
     * The states of the product reservation.
     */
    public const array STATES = [
        self::STATE_WAITING,
        self::STATE_PENDING,
        self::STATE_ACCEPTED,
        self::STATE_REJECTED,
        self::STATE_CANCELED_BY_CLIENT,
        self::STATE_CANCELED_BY_SPONSOR,
        self::STATE_CANCELED_BY_PROVIDER,
        self::STATE_CANCELED_PAYMENT_FAILED,
        self::STATE_CANCELED_PAYMENT_EXPIRED,
        self::STATE_CANCELED_PAYMENT_CANCELED,
    ];

    /**
     * The states of a canceled product reservation.
     */
    public const array STATES_CANCELED = [
        self::STATE_CANCELED_BY_CLIENT,
        self::STATE_CANCELED_BY_SPONSOR,
        self::STATE_CANCELED_BY_PROVIDER,
        self::STATE_CANCELED_PAYMENT_FAILED,
        self::STATE_CANCELED_PAYMENT_EXPIRED,
        self::STATE_CANCELED_PAYMENT_CANCELED,
    ];

    /**
     * The number of days the transaction payout has to be delayed.
     */
    public const int TRANSACTION_DELAY = 14;

    /**
     * @var string[]
     */
    protected $fillable = [
        'product_id', 'voucher_id', 'voucher_transaction_id', 'fund_provider_product_id',
        'amount', 'amount_voucher', 'state', 'accepted_at', 'rejected_at', 'canceled_at',
        'price', 'price_type', 'price_discount', 'code', 'note', 'employee_id',
        'first_name', 'last_name', 'user_note', 'phone', 'address', 'birth_date', 'archived',
        'street', 'house_nr', 'house_nr_addition', 'postal_code', 'city', 'amount_extra',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'archived' => 'boolean',
        'birth_date' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    /**
     * @throws Exception
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
        return trim($value ?: sprintf('%s %s', $this->street ?: '', implode(', ', array_filter([
            $this->house_nr, $this->house_nr_addition, $this->postal_code, $this->city,
        ]))));
    }

    /**
     * @return Carbon
     * @noinspection PhpUnused
     */
    public function getExpireAtAttribute(): Carbon
    {
        return $this->voucher->calcExpireDateForProduct();
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
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function fund_provider_product_with_trashed(): BelongsTo
    {
        return $this->belongsTo(FundProviderProduct::class, 'fund_provider_product_id')->withTrashed();
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
        return trans('states.product_reservations.' . $this->state);
    }

    /**
     * @return bool
     */
    public function isExpired(): bool
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
     * @noinspection PhpUnused
     */
    public function isCanceledBySponsor(): bool
    {
        return $this->state === self::STATE_CANCELED_BY_SPONSOR;
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
        return !$this->archived && (
            $this->isAccepted() ||
            $this->isRejected() ||
            $this->isCanceled() ||
            $this->isExpired()
        );
    }

    /**
     * @param Employee|null $employee
     * @throws Throwable
     * @return $this
     */
    public function acceptProvider(?Employee $employee = null): self
    {
        DB::transaction(function () use ($employee) {
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
            'amount_voucher' => $this->amount_voucher,
            'product_id' => $this->product_id,
            'transfer_at' => $transfer_at,
            'employee_id' => $employee?->id,
            'branch_id' => $employee?->office?->branch_id,
            'branch_name' => $employee?->office?->branch_name,
            'branch_number' => $employee?->office?->branch_number,
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
     * @throws Throwable
     * @return $this
     */
    public function rejectOrCancelProvider(?Employee $employee = null): self
    {
        DB::transaction(function () use ($employee) {
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
                $this->voucher_transaction->cancelPending($employee, false);
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
    public function isRejected(): bool
    {
        return $this->state === self::STATE_REJECTED;
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
     * @return bool
     */
    public function isCancelableBySponsor(): bool
    {
        return $this->isCancelableByRequester();
    }

    /**
     * @return Voucher
     */
    public function makeVoucher(): Voucher
    {
        return $this->voucher->buyProductVoucher($this->product, $this);
    }

    /**
     * @throws Throwable
     * @return bool|null
     */
    public function cancelByClient(): ?bool
    {
        return $this->cancelByState(self::STATE_CANCELED_BY_CLIENT);
    }

    /**
     * @throws Throwable
     * @return bool|null
     */
    public function cancelBySponsor(): ?bool
    {
        return $this->cancelByState(self::STATE_CANCELED_BY_SPONSOR);
    }

    /**
     * @param string $state
     * @throws Throwable
     * @return bool
     */
    public function cancelByState(string $state): bool
    {
        DB::transaction(function () use ($state) {
            $this->product_voucher?->delete();

            $this->update([
                'state' => $state,
                'canceled_at' => now(),
            ]);

            Event::dispatch(new ProductReservationCanceled($this));
        });

        return true;
    }

    /**
     * @param string|null $identity_address
     * @param string|null $note
     * @throws Throwable
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

    /**
     * @param Implementation $implementation
     * @return Payment|null
     */
    public function createExtraPayment(Implementation $implementation): ?Payment
    {
        return $this->createExtraPaymentMollie($implementation, $this->amount_extra);
    }

    /**
     * @param Employee|null $employee
     * @throws MollieException|Throwable
     * @return ReservationExtraPayment|null
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
     * @throws MollieException|Throwable
     * @return ReservationExtraPaymentRefund|null
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
            $expiresIn = now()->diffInSeconds($expireAt);

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
            !$this->isExpired() &&
            !$this->product->trashed() &&
            (!$this->extra_payment || $this->extra_payment->isPaid()) &&
            (!$this->extra_payment || $this->extra_payment->refunds_active->isEmpty());
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
            'method' => MollieServiceInterface::PAYMENT_METHOD_IDEAL,
            'amount' => $amount,
            'currency' => $currency,
            'description' => trans('extra-payments.payment.description'),
            'redirect_url' => $implementation->urlWebshop("/reservations/$this->id?checkout=1"),
            'cancel_url' => $implementation->urlWebshop("/reservations/$this->id"),
        ]);

        if (!$payment) {
            return null;
        }

        $extraPayment = $this->extra_payment()->create([
            'type' => ReservationExtraPayment::TYPE_MOLLIE,
            'state' => $payment->status,
            'amount' => $amount,
            'currency' => $currency,
            'method' => $payment->method,
            'expires_at' => $payment->expires_at?->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
            'payment_id' => $payment->id,
        ]);

        Event::dispatch(new ReservationExtraPaymentCreated($extraPayment));

        return $payment;
    }
}
