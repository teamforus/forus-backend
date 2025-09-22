<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\FundPeriod.
 *
 * @property int $id
 * @property int $fund_id
 * @property string $state
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund $fund
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundPeriod newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundPeriod newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundPeriod query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundPeriod whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundPeriod whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundPeriod whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundPeriod whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundPeriod whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundPeriod whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundPeriod whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundPeriod extends Model
{
    public const string STATE_PENDING = 'pending';
    public const string STATE_ACTIVE = 'active';
    public const string STATE_ENDED = 'ended';

    protected $fillable = [
        'state',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * @return BelongsTo
     */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @return void
     */
    public function setEnded(): void
    {
        $this->update([
            'state' => self::STATE_ENDED,
        ]);
    }

    /**
     * @return bool
     */
    public function hasEnded(): bool
    {
        return $this->state === self::STATE_ENDED;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->state === self::STATE_ACTIVE;
    }

    /**
     * @return void
     */
    public function activate(): void
    {
        $this->update([
            'state' => self::STATE_ACTIVE,
        ]);

        $this->fund->update([
            'state' => Fund::STATE_ACTIVE,
            'archived' => false,
            'end_date' => $this->end_date,
            'start_date' => $this->start_date,
        ]);

        $this->fund->log(Fund::EVENT_PERIOD_EXTENDED, [
            'fund' => $this->fund,
            'fund_period' => $this,
            'organization' => $this->fund->organization,
        ]);
    }
}
