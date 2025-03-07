<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\PreCheckRecordSetting.
 *
 * @property int $id
 * @property int $pre_check_record_id
 * @property int $fund_id
 * @property string|null $description
 * @property int $impact_level
 * @property bool $is_knock_out
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund $fund
 * @property-read \App\Models\PreCheckRecord $pre_check_record
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreCheckRecordSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreCheckRecordSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreCheckRecordSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreCheckRecordSetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreCheckRecordSetting whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreCheckRecordSetting whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreCheckRecordSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreCheckRecordSetting whereImpactLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreCheckRecordSetting whereIsKnockOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreCheckRecordSetting wherePreCheckRecordId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PreCheckRecordSetting whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PreCheckRecordSetting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'pre_check_record_id', 'fund_id', 'description', 'impact_level', 'is_knock_out',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'is_knock_out' => 'boolean',
    ];

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function pre_check_record(): BelongsTo
    {
        return $this->belongsTo(PreCheckRecord::class);
    }

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }
}
