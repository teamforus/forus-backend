<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\FundProviderUnsubscribe.
 *
 * @property int $id
 * @property int $fund_provider_id
 * @property string|null $note
 * @property bool $canceled
 * @property \Illuminate\Support\Carbon $unsubscribe_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\FundProvider $fund_provider
 * @property-read bool $is_expired
 * @property-read string $state
 * @property-read string $state_locale
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundProviderUnsubscribe newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundProviderUnsubscribe newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundProviderUnsubscribe query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundProviderUnsubscribe whereCanceled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundProviderUnsubscribe whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundProviderUnsubscribe whereFundProviderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundProviderUnsubscribe whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundProviderUnsubscribe whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundProviderUnsubscribe whereUnsubscribeAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundProviderUnsubscribe whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundProviderUnsubscribe extends BaseModel
{
    public const string STATE_PENDING = 'pending';
    public const string STATE_APPROVED = 'approved';
    public const string STATE_CANCELED = 'canceled';

    public const array STATES = [
        self::STATE_PENDING,
        self::STATE_APPROVED,
        self::STATE_CANCELED,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'fund_provider_id', 'note', 'unsubscribe_at', 'canceled',
    ];

    /**
     * @var true[]
     */
    protected $casts = [
        'canceled' => 'boolean',
        'unsubscribe_at' => 'datetime',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fund_provider(): BelongsTo
    {
        return $this->belongsTo(FundProvider::class);
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->fund_provider->isAccepted() && $this->unsubscribe_at->endOfDay()->isPast();
    }

    /**
     * @return bool
     */
    public function isPending(): bool
    {
        return !$this->canceled && $this->fund_provider->isAccepted();
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getStateAttribute(): string
    {
        if ($this->canceled) {
            return 'canceled';
        }

        if ($this->is_expired) {
            return 'overdue';
        }

        return $this->isPending() ? 'pending' : 'approved';
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getStateLocaleAttribute(): string
    {
        return trans('fund-unsubscribes.states.' . $this->state);
    }
}
