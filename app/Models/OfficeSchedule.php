<?php

namespace App\Models;

/**
 * App\Models\OfficeSchedule
 *
 * @property int $id
 * @property int $office_id
 * @property int $week_day
 * @property string|null $start_time
 * @property string|null $end_time
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
 * @property-read \App\Models\Office $office
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OfficeSchedule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OfficeSchedule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OfficeSchedule query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OfficeSchedule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OfficeSchedule whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OfficeSchedule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OfficeSchedule whereOfficeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OfficeSchedule whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OfficeSchedule whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OfficeSchedule whereWeekDay($value)
 * @mixin \Eloquent
 */
class OfficeSchedule extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'office_id', 'week_day', 'start_time', 'end_time'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'break_start', 'break_end'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function office() {
        return $this->belongsTo(Office::class);
    }
 }
