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
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPaymentRefund newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPaymentRefund newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPaymentRefund query()
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPaymentRefund whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPaymentRefund whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPaymentRefund whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPaymentRefund whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPaymentRefund whereRefundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPaymentRefund whereReservationExtraPaymentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPaymentRefund whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReservationExtraPaymentRefund whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ReservationExtraPaymentRefund extends Model
{
    public const STATE_QUEUED = 'queued';
    public const STATE_REFUNDED = 'refunded';
    public const STATE_CANCELED = 'canceled';
    public const STATE_PROCESSING = 'processing';
    public const STATE_PENDING = 'pending';
    public const STATE_FAILED = 'failed';

    public const STATES = [
        self::STATE_QUEUED,
        self::STATE_PENDING,
        self::STATE_PROCESSING,
        self::STATE_CANCELED,
        self::STATE_FAILED,
        self::STATE_REFUNDED,
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
        return trans("states/reservation_extra_payment_refunds.$this->state");
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
