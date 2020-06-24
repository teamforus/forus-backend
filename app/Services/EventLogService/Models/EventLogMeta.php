<?php

namespace App\Services\EventLogService\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\EventLogMeta
 *
 * @property-read \App\Models\EventLog $event_log
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EventLogMeta newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EventLogMeta newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EventLogMeta query()
 * @mixin \Eloquent
 */
class EventLogMeta extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'key', 'value'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function event_log() {
        return $this->belongsTo(EventLog::class);
    }
}
