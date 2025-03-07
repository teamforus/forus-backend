<?php

namespace App\Models;

/**
 * App\Models\OfficeSchedule.
 *
 * @property int $id
 * @property int $office_id
 * @property int $week_day
 * @property string|null $start_time
 * @property string|null $end_time
 * @property string|null $break_start_time
 * @property string|null $break_end_time
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Office $office
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OfficeSchedule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OfficeSchedule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OfficeSchedule query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OfficeSchedule whereBreakEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OfficeSchedule whereBreakStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OfficeSchedule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OfficeSchedule whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OfficeSchedule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OfficeSchedule whereOfficeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OfficeSchedule whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OfficeSchedule whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OfficeSchedule whereWeekDay($value)
 * @mixin \Eloquent
 */
class OfficeSchedule extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'office_id', 'week_day', 'start_time', 'end_time',
        'break_start_time', 'break_end_time',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'break_start', 'break_end',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function office()
    {
        return $this->belongsTo(Office::class);
    }
}
