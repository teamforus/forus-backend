<?php

namespace App\Models;

use App\Events\ReservationExtraPayments\ReservationExtraPaymentCanceled;
use App\Events\ReservationExtraPayments\ReservationExtraPaymentExpired;
use App\Events\ReservationExtraPayments\ReservationExtraPaymentFailed;
use App\Events\ReservationExtraPayments\ReservationExtraPaymentPaid;
use App\Events\ReservationExtraPayments\ReservationExtraPaymentRefunded;
use App\Events\ReservationExtraPayments\ReservationExtraPaymentRefundedApi;
use App\Events\ReservationExtraPayments\ReservationExtraPaymentUpdated;
use App\Models\Traits\UpdatesModel;
use App\Services\EventLogService\Traits\HasLogs;
use App\Services\MollieService\Exceptions\MollieException;
use App\Services\MollieService\Models\MollieConnection;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Event;
use Mollie\Api\Resources\Payment;
use Mollie\Api\Resources\Refund;

/**
 * App\Models\ReservationExtraPayment
 *
 * @property int $id
 * @property int $product_reservation_id
 * @property string $type
 * @property string|null $payment_id
 * @property string|null $method
 * @property string $state
 * @property string $amount
 * @property string|null $amount_refunded
 * @property string|null $amount_captured
 * @property string|null $amount_remaining
 * @property string $currency
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $canceled_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read string $amount_locale
 * @property-read string $amount_refunded_locale
 * @property-read string $state_locale
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\EventLogService\Models\EventLog[] $logs
 * @property-read int|null $logs_count
 * @property-read \App\Models\ProductReservation $product_reservation
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ReservationExtraPaymentRefund[] $refunds
 * @property-read int|null $refunds_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ReservationExtraPaymentRefund[] $refunds_active
 * @property-read int|null $refunds_active_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ReservationExtraPaymentRefund[] $refunds_completed
 * @property-read int|null $refunds_completed_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ReservationExtraPaymentRefund[] $refunds_pending
 * @property-read int|null $refunds_pending_count
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment query()
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment whereAmountCaptured($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment whereAmountRefunded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment whereAmountRemaining($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment whereCanceledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment wherePaymentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment whereProductReservationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment withoutTrashed()
 * @mixin \Eloquent
 */
class ReservationExtraPayment extends Model
{
    use UpdatesModel, HasLogs, SoftDeletes;

    public const TYPE_MOLLIE = 'mollie';

    public const STATE_PAID = 'paid';
    public const STATE_CANCELED = 'canceled';
    public const STATE_OPEN = 'open';
    public const STATE_PENDING = 'pending';
    public const STATE_FAILED = 'failed';
    public const STATE_EXPIRED = 'expired';

    public const EVENT_CREATED = 'created';
    public const EVENT_UPDATED = 'updated';
    public const EVENT_FAILED = 'failed';
    public const EVENT_EXPIRED = 'expired';
    public const EVENT_PAID = 'paid';
    public const EVENT_CANCELED = 'canceled';
    public const EVENT_REFUNDED = 'refunded';
    public const EVENT_REFUNDED_API = 'refunded_api';

    public const CANCELED_STATES = [
        self::STATE_FAILED,
        self::STATE_EXPIRED,
        self::STATE_CANCELED,
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'payment_id', 'type', 'state', 'method',
        'amount', 'amount_refunded', 'amount_captured', 'amount_remaining',
        'product_reservation_id', 'paid_at', 'expires_at', 'canceled_at', 'currency',
    ];

    /**
     * @var string[]
     */
    protected $dates = [
        'paid_at', 'expires_at', 'canceled_at',
    ];

    /**
     * @return BelongsTo
     */
    public function product_reservation(): BelongsTo
    {
        return $this->belongsTo(ProductReservation::class);
    }

    /**
     * @return HasMany
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(ReservationExtraPaymentRefund::class);
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function refunds_active(): HasMany
    {
        return $this
            ->hasMany(ReservationExtraPaymentRefund::class)
            ->whereNotIn('state', ReservationExtraPaymentRefund::CANCELED_STATES);
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function refunds_pending(): HasMany
    {
        return $this
            ->hasMany(ReservationExtraPaymentRefund::class)
            ->where('state', ReservationExtraPaymentRefund::STATE_PENDING);
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function refunds_completed(): HasMany
    {
        return $this
            ->hasMany(ReservationExtraPaymentRefund::class)
            ->where('state', ReservationExtraPaymentRefund::STATE_REFUNDED);
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getStateLocaleAttribute(): string
    {
        return trans("states/reservation_extra_payments.$this->state");
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getAmountLocaleAttribute(): string
    {
        return currency_format_locale($this->amount);
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getAmountRefundedLocaleAttribute(): string
    {
        return currency_format_locale($this->amount_refunded);
    }

    /**
     * @param Employee|null $employee
     * @param array $extraModels
     * @return array
     */
    public function getLogModels(?Employee $employee = null, array $extraModels = []): array
    {
        return array_merge([
            'employee' => $employee,
            'product_reservation' => $this->product_reservation,
            'reservation_extra_payment' => $this,
        ], $extraModels);
    }

    /**
     * @param Employee|null $employee
     * @return ReservationExtraPayment|null
     * @throws MollieException
     * @throws \Throwable
     */
    public function fetchAndUpdateMolliePayment(?Employee $employee): ?ReservationExtraPayment
    {
        if (!$this->payment_id || !$this->getMollieConnection()?->onboardingComplete()) {
            return null;
        }

        $reservation = $this->product_reservation;
        $payment = $this->getMollieConnection()->getMollieService()->getPayment($this->payment_id);
        $becomePaid = !$this->isPaid() && $payment->isPaid();
        $becomeFailed = !$this->isFailed() && $payment->isFailed();
        $becomeExpired = !$this->isExpired() && $payment->isExpired();
        $becomeCanceled = !$this->isCanceled() && $payment->isCanceled();
        $becomeRefunded = !$this->isFullyRefunded() && $payment->amountRefunded?->value >= $this->amount;

        $this->fetchMollieRefunds();

        log_debug([
            'state' => $payment->status,
            'paid_at' => $payment->paidAt ? Carbon::parse($payment->paidAt) : null,
            'canceled_at' => $payment->canceledAt ? Carbon::parse($payment->canceledAt) : null,
            'amount' => $payment->amount?->value,
            'amount_captured' => $payment->amountCaptured?->value,
            'amount_refunded' => $payment->amountRefunded?->value,
            'amount_remaining' => $payment->amountRemaining?->value,
        ]);

        $this->update([
            'state' => $payment->status,
            'paid_at' => $payment->paidAt ? Carbon::parse($payment->paidAt) : null,
            'canceled_at' => $payment->canceledAt ? Carbon::parse($payment->canceledAt) : null,
            'amount' => $payment->amount?->value,
            'amount_captured' => $payment->amountCaptured?->value,
            'amount_refunded' => $payment->amountRefunded?->value,
            'amount_remaining' => $payment->amountRemaining?->value,
        ]);

        Event::dispatch(new ReservationExtraPaymentUpdated($this, $employee));

        if ($becomeRefunded) {
            Event::dispatch(new ReservationExtraPaymentRefunded($this, $employee));
        }

        if ($becomeFailed) {
            $reservation->cancelByState($reservation::STATE_CANCELED_PAYMENT_FAILED);
            Event::dispatch(new ReservationExtraPaymentFailed($this, $employee));
        }

        if ($becomePaid) {
            $reservation->setPending();

            if ($reservation->product->autoAcceptsReservations($reservation->voucher->fund)) {
                $reservation->acceptProvider();
            }

            Event::dispatch(new ReservationExtraPaymentPaid($this, $employee));
        }

        if ($becomeCanceled) {
            $reservation->cancelByState($reservation::STATE_CANCELED_PAYMENT_CANCELED);
            Event::dispatch(new ReservationExtraPaymentCanceled($this, $employee));
        }

        if ($becomeExpired) {
            $reservation->cancelByState($reservation::STATE_CANCELED_PAYMENT_EXPIRED);
            Event::dispatch(new ReservationExtraPaymentExpired($this, $employee));
        }

        return $this;
    }

    /**
     * @return void
     * @throws MollieException
     */
    private function fetchMollieRefunds(): void
    {
        if (!$this->payment_id) {
            return;
        }

        /** @var Refund[] $refunds */
        $refunds = $this->getMollieConnection()
            ->getMollieService()
            ->getPaymentRefunds($this->payment_id);

        foreach ($refunds as $refund) {
            log_debug([
                'refund_id' => $refund->id,
                'state' => $refund->status,
                'amount' => $refund->amount->value,
                'currency' => $refund->amount->currency,
            ]);

            $this->refunds()->updateOrCreate([
                'refund_id' => $refund->id,
            ], [
                'state' => $refund->status,
                'amount' => $refund->amount->value,
                'currency' => $refund->amount->currency,
            ]);
        }
    }

    /**
     * @param Employee|null $employee
     * @return ReservationExtraPaymentRefund|null
     * @throws MollieException
     * @throws \Throwable
     */
    public function createMollieRefund(?Employee $employee): ?ReservationExtraPaymentRefund
    {
        if (!$this->getMollieConnection()->onboardingComplete()) {
            return null;
        }

        $refund = $this->getMollieConnection()->getMollieService()->refundPayment($this->payment_id, [
            'amount' => $this->availableRefundAmount(),
            'currency' => $this->currency,
            'description' => trans('extra-payments.refund.description'),
        ]);

        $reservationExtraPaymentRefund = $this->refunds()->create([
            'refund_id' => $refund->id,
            'state' => $refund->status,
            'amount' => $refund->amount->value,
            'currency' => $refund->amount->currency,
        ]);

        Event::dispatch(new ReservationExtraPaymentRefundedApi($this, $employee, [
            'refund_id' => $refund->id,
        ]));

        $this->fetchAndUpdateMolliePayment($employee);

        return $reservationExtraPaymentRefund;
    }

    /**
     * @return bool
     */
    public function isPaid(): bool
    {
        return $this->state === self::STATE_PAID;
    }

    /**
     * @return bool
     */
    public function isPending(): bool
    {
        return in_array($this->state, [
            self::STATE_OPEN,
            self::STATE_PENDING,
        ], true);
    }

    /**
     * @return bool
     */
    public function isCancelable(): bool
    {
        if ($this->isMollieType()) {
            return $this->checkCancelableMollie();
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isRefundable(): bool
    {
        return $this->availableRefundAmount() > 0;
    }

    /**
     * @return Payment|null
     */
    public function getPayment(): ?Payment
    {
        return $this->getMollieConnection()?->readPayment($this->payment_id);
    }

    /**
     * @return bool
     */
    public function isCanceled(): bool
    {
        return in_array($this->state, self::CANCELED_STATES, true);
    }


    /**
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->state === self::STATE_FAILED;
    }

    /**
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * @return int|null
     */
    public function expiresIn(): ?int
    {
        return $this->expires_at ? max(now()->diffInSeconds($this->expires_at, false), 0) : null;
    }

    /**
     * @return bool
     */
    public function isFullyRefunded(): bool
    {
        return $this->isPaid() && $this->refunds_completed->sum('amount') >= $this->amount;
    }

    /**
     * @return bool
     */
    public function isPartlyRefunded(): bool
    {
        return $this->isPaid() && $this->refunds_completed->sum('amount') > 0;
    }

    /**
     * @return bool
     */
    public function hasPendingRefunds(): bool
    {
        return $this->refunds_pending->isNotEmpty();
    }

    /**
     * @return float|int
     */
    public function availableRefundAmount(): float|int
    {
        return $this->amount - $this->refunds_active->sum('amount');
    }

    /**
     * @return bool
     */
    public function isMollieType(): bool
    {
        return $this->type === self::TYPE_MOLLIE;
    }

    /**
     * @return bool
     */
    public function checkCancelableMollie(): bool
    {
        return false;
    }

    /**
     * @return MollieConnection|null
     */
    protected function getMollieConnection(): ?MollieConnection
    {
        return $this->product_reservation->product?->organization?->mollie_connection;
    }
}
