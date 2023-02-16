<?php

namespace App\Models;

use App\Searches\FundUnsubscribeSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\FundUnsubscribe
 *
 * @property int $id
 * @property int $fund_provider_id
 * @property string $note
 * @property string $state
 * @property bool $is_expired
 * @property FundProvider $fund_provider
 * @property \Illuminate\Support\Carbon|null $unsubscribe_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|FundUnsubscribe newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundUnsubscribe newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundUnsubscribe query()
 * @method static \Illuminate\Database\Eloquent\Builder|FundUnsubscribe whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundUnsubscribe whereFundProviderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundUnsubscribe whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundUnsubscribe whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundUnsubscribe whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundUnsubscribe whereUnsubscribeDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundUnsubscribe whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundUnsubscribe extends BaseModel
{
    use HasFactory;

    public const STATE_PENDING = 'pending';
    public const STATE_APPROVED = 'approved';
    public const STATE_CANCELED = 'canceled';
    public const STATE_EXPIRED = 'expired';

    public const STATES = [
        self::STATE_PENDING,
        self::STATE_APPROVED,
        self::STATE_CANCELED,
        self::STATE_EXPIRED,
    ];

    /**
     * @var string[]
     */
    protected $dates = [
        'unsubscribe_date',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'fund_provider_id', 'note', 'unsubscribe_date', 'state'
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
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->state == self::STATE_PENDING && $this->unsubscribe_date->isPast();
    }

    /**
     * @param Organization $organization
     * @param array $filters
     * @return Builder
     */
    public static function searchProvider(
        Organization $organization,
        array $filters
    ): Builder {
        $search = new FundUnsubscribeSearch(array_merge($filters, [
            'provider_organization' => $organization
        ]));

        return $search->query();
    }

    /**
     * @param Organization $organization
     * @param array $filters
     * @return Builder
     */
    public static function searchSponsor(
        Organization $organization,
        array $filters
    ): Builder {
        $search = new FundUnsubscribeSearch(array_merge($filters, [
            'sponsor_organization' => $organization
        ]));

        return $search->query();
    }
}
