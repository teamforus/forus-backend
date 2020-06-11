<?php

namespace App\Services\EventLogService\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * App\Services\EventLogService\Models\EventLog
 *
 * @property int $id
 * @property string $loggable_type
 * @property int $loggable_id
 * @property string $event
 * @property string|null $identity_address
 * @property array $data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $loggable
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\EventLogService\Models\EventLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\EventLogService\Models\EventLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\EventLogService\Models\EventLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\EventLogService\Models\EventLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\EventLogService\Models\EventLog whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\EventLogService\Models\EventLog whereEvent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\EventLogService\Models\EventLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\EventLogService\Models\EventLog whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\EventLogService\Models\EventLog whereLoggableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\EventLogService\Models\EventLog whereLoggableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\EventLogService\Models\EventLog whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class EventLog extends Model
{
    protected $fillable = [
        'event', 'data', 'identity_address'
    ];

    protected $casts = [
        'data' => 'array',
    ];

    /*protected $hidden = [
        'event', 'data', 'identity_address'
    ];*/

    /**
     * @return MorphTo
     */
    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param string $loggable_class
     * @param int|array $loggable_key
     * @return Builder
     */
    public static function eventsOfTypeQuery(
        string $loggable_class,
        $loggable_key
    ): Builder {
        $query = self::query();

        $query->whereHasMorph('loggable', $loggable_class);
        $query->where(static function(Builder $builder) use ($loggable_key) {
            $builder->whereIn('loggable_id', (array) $loggable_key);
        });

        return $query;
    }
}
