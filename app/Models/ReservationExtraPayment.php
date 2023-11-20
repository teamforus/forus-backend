<?php

namespace App\Models;

use App\Events\ReservationExtraPayments\ReservationExtraPaymentCanceled;
use App\Events\ReservationExtraPayments\ReservationExtraPaymentPaid;
use App\Events\ReservationExtraPayments\ReservationExtraPaymentUpdated;
use App\Models\Traits\UpdatesModel;
use App\Services\EventLogService\Traits\HasLogs;
use App\Services\MollieService\Exceptions\MollieApiException;
use App\Services\MollieService\MollieService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
 * @property string $currency
 * @property bool $refunded
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property \Illuminate\Support\Carbon|null $canceled_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $state_locale
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\EventLogService\Models\EventLog[] $logs
 * @property-read int|null $logs_count
 * @property-read \App\Models\ProductReservation $product_reservation
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ReservationExtraPaymentRefund[] $refunds
 * @property-read int|null $refunds_count
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment query()
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment whereCanceledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment wherePaymentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment whereProductReservationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment whereRefunded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPayment whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ReservationExtraPayment extends Model
{
    use UpdatesModel, HasLogs;

    public const TYPE_MOLLIE = 'mollie';

    public const STATE_PAID = 'paid';
    public const STATE_CANCELED = 'canceled';
    public const STATE_OPEN = 'open';
    public const STATE_PENDING = 'pending';
    public const STATE_FAILED = 'failed';
    public const STATE_EXPIRED = 'expired';

    public const EVENT_CREATED = 'created';
    public const EVENT_UPDATED = 'updated';
    public const EVENT_PAID = 'paid';
    public const EVENT_CANCELED = 'canceled';
    public const EVENT_REFUNDED = 'refunded';

    public const CANCELED_STATES = [
        self::STATE_CANCELED,
        self::STATE_FAILED,
        self::STATE_EXPIRED,
    ];

    public const CANCELABLE_STATES = [
        self::STATE_OPEN,
        self::STATE_PENDING,
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'payment_id', 'type', 'state', 'method', 'amount', 'product_reservation_id',
        'paid_at', 'canceled_at', 'refunded', 'currency',
    ];

    /**
     * @var string[]
     */
    protected $dates = [
        'paid_at', 'canceled_at',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'refunded' => 'boolean',
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
     * @return string
     * @noinspection PhpUnused
     */
    public function getStateLocaleAttribute(): string
    {
        return trans('states/reservation_extra_payments.' . $this->state);
    }

    /**
     * @param array $extraModels
     * @return array
     */
    public function getLogModels(array $extraModels = []): array
    {
        return array_merge([
            'product_reservation' => $this->product_reservation,
            'reservation_extra_payment' => $this,
        ], $extraModels);
    }

    /**
     * @return ReservationExtraPayment|null
     */
    public function fetchAndUpdateMolliePayment(): ?ReservationExtraPayment
    {
        $connection = $this->product_reservation->product->organization->mollie_connection_active;

        if (!$connection) {
            return null;
        }

        try {
            $mollieService = new MollieService($connection);
            $payment = $mollieService->readPayment($this->payment_id);

            $becomePaid = !$this->paid_at && $payment->isPaid();
            $becomeCanceled = !$this->canceled_at && $payment->isCanceled();
            $becomeRefunded = !$this->refunded && $payment->amountRefunded->value >= $this->amount;

            $this->fetchMollieRefunds($mollieService);

            $this->update([
                'state' => $payment->status,
                'paid_at' => $payment->paidAt ? Carbon::parse($payment->paidAt) : null,
                'canceled_at' => $payment->canceledAt ? Carbon::parse($payment->canceledAt) : null,
                'refunded' => $payment->amountRefunded->value >= $this->amount,
            ]);

            ReservationExtraPaymentUpdated::dispatch($this);

            if ($becomeRefunded) {
                ReservationExtraPaymentPaid::dispatch($this);
            }

            if ($becomePaid) {
                ReservationExtraPaymentPaid::dispatch($this);
            }

            if ($becomeCanceled) {
                ReservationExtraPaymentCanceled::dispatch($this);
            }

            return $this;
        } catch (MollieApiException $e) {}

        return null;
    }

    /**
     * @param MollieService $mollieService
     * @return void
     * @throws MollieApiException
     */
    private function fetchMollieRefunds(MollieService $mollieService): void
    {
        $refunds = $mollieService->readPaymentRefunds($this->payment_id);

        /** @var Refund $refund */
        foreach ($refunds as $refund) {
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
     * @return ReservationExtraPaymentRefund|null
     * @throws MollieApiException
     */
    public function createMollieRefund(): ?ReservationExtraPaymentRefund
    {
        $connection = $this->product_reservation->product->organization->mollie_connection_active;

        if (!$connection) {
            return null;
        }

        $mollieService = new MollieService($connection);
        $refund = $mollieService->refundPayment(
            $this->payment_id,
            array_merge($this->only('amount', 'currency'), [
                'description' => trans('extra-payments.refund.description'),
            ])
        );

        return $this->refunds()->create([
            'refund_id' => $refund->id,
            'state' => $refund->status,
            'amount' => $refund->amount->value,
            'currency' => $refund->amount->currency,
        ]);
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
            self::STATE_PENDING,
            self::STATE_OPEN,
        ]);
    }

    /**
     * @return bool
     */
    public function isCancelable(): bool
    {
        if ($this->type === self::TYPE_MOLLIE) {
            if (in_array($this->state, self::CANCELABLE_STATES)) {
                return $this->checkCancelableMollie();
            }

            return in_array($this->state, self::CANCELED_STATES);
        }

        return !$this->isPaid();
    }

    /**
     * @return bool
     */
    public function checkCancelableMollie(): bool
    {
        $connection = $this->product_reservation->product->organization->mollie_connection_active;

        return (bool)$connection?->readPayment($this->payment_id)?->isCancelable;

    }

    /**
     * @return bool
     */
    public function isFullRefunded(): bool
    {
        return $this->refunded;
    }
}
