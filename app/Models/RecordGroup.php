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
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\RecordGroupRecordType[] $record_group_record_types
 * @property-read int|null $record_group_record_types_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordGroup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordGroup whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordGroup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordGroup whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordGroup whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordGroup whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordGroup whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class RecordGroup extends Model
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
    public function record_group_record_types(): HasMany
    {
        return $this->hasMany(RecordGroupRecordType::class);
    }

    /**
     * @return Collection
     */
    public static function getCachedList(): Collection
    {
        $cacheKey = 'record_groups';
        $cacheDuration = 60 * 5;

        return Cache::store('array')->remember($cacheKey, $cacheDuration, function () {
            return static::with('record_group_record_types')->get();
        });
    }
}
