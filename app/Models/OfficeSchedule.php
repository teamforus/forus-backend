<?php

namespace App\Models;


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
