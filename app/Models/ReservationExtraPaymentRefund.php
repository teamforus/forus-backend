<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\ReservationExtraPaymentRefund
 *
 * @property int $id
 * @property int $reservation_extra_payment_id
 * @property string|null $refund_id
 * @property string $state
 * @property string $amount
 * @property string $currency
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $amount_locale
 * @property-read string $state_locale
 * @property-read \App\Models\ProductReservation $reservation_extra_payment
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationExtraPaymentRefund newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationExtraPaymentRefund newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationExtraPaymentRefund query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationExtraPaymentRefund whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationExtraPaymentRefund whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationExtraPaymentRefund whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationExtraPaymentRefund whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationExtraPaymentRefund whereRefundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationExtraPaymentRefund whereReservationExtraPaymentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationExtraPaymentRefund whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationExtraPaymentRefund whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ReservationExtraPaymentRefund extends Model
{
    public const string STATE_QUEUED = 'queued';
    public const string STATE_REFUNDED = 'refunded';
    public const string STATE_CANCELED = 'canceled';
    public const string STATE_PROCESSING = 'processing';
    public const string STATE_PENDING = 'pending';
    public const string STATE_FAILED = 'failed';

    public const array STATES = [
        self::STATE_QUEUED,
        self::STATE_PENDING,
        self::STATE_PROCESSING,
        self::STATE_CANCELED,
        self::STATE_FAILED,
        self::STATE_REFUNDED,
    ];

    public const array PENDING_STATES = [
        self::STATE_QUEUED,
        self::STATE_PENDING,
        self::STATE_PROCESSING,
    ];

    public const array CANCELED_STATES = [
        self::STATE_FAILED,
        self::STATE_CANCELED,
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'reservation_extra_payment_id', 'refund_id', 'state', 'currency', 'amount',
    ];

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function reservation_extra_payment(): BelongsTo
    {
        return $this->belongsTo(ProductReservation::class);
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getStateLocaleAttribute(): string
    {
        return trans("states.reservation_extra_payment_refunds.$this->state");
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
     * @return bool
     * @noinspection PhpUnused
     */
    public function isRefunded(): bool
    {
        return $this->state === self::STATE_REFUNDED;
    }
}
