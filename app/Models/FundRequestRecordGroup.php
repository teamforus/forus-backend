<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property string $title
 * @property int|null $organization_id
 * @property int|null $fund_id
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund|null $fund
 * @property-read \App\Models\Organization|null $organization
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundRequestRecordGroupRecord[] $records
 * @property-read int|null $records_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestRecordGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestRecordGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestRecordGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestRecordGroup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestRecordGroup whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestRecordGroup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestRecordGroup whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestRecordGroup whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestRecordGroup whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestRecordGroup whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundRequestRecordGroup extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'title', 'organization_id', 'fund_id', 'order',
    ];

    /**
     * @return BelongsTo
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo
     */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @return HasMany
     */
    public function records(): HasMany
    {
        return $this->hasMany(FundRequestRecordGroupRecord::class);
    }

    /**
     * @return Collection
     */
    public static function getCachedList(): Collection
    {
        $cacheKey = 'fund_request_record_groups';
        $cacheDuration = 60 * 5;

        return Cache::store('array')->remember($cacheKey, $cacheDuration, function () {
            return static::with('records')->get();
        });
    }
}
