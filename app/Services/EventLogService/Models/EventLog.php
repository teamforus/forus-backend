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
 * @property-read Model|\Eloquent $loggable
 * @method static Builder|EventLog newModelQuery()
 * @method static Builder|EventLog newQuery()
 * @method static Builder|EventLog query()
 * @method static Builder|EventLog whereCreatedAt($value)
 * @method static Builder|EventLog whereData($value)
 * @method static Builder|EventLog whereEvent($value)
 * @method static Builder|EventLog whereId($value)
 * @method static Builder|EventLog whereIdentityAddress($value)
 * @method static Builder|EventLog whereLoggableId($value)
 * @method static Builder|EventLog whereLoggableType($value)
 * @method static Builder|EventLog whereUpdatedAt($value)
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

    protected $hidden = [
        /*'event',*/ 'data', 'identity_address'
    ];

    /**
     * @return MorphTo
     */
    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * todo: migrate all eventsOfTypeQuery to eventsOfTypeQuery2
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

    /**
     * @param string $loggableType
     * @param Builder $loggableQuery
     * @return Builder
     */
    public static function eventsOfTypeQuery2(
        string $loggableType,
        Builder $loggableQuery
    ): Builder {
        $query = self::query();

        $query->whereHasMorph('loggable', $loggableType);
        $query->where(static function(Builder $builder) use ($loggableQuery) {
            $builder->whereIn('loggable_id', $loggableQuery->select('id'));
        });

        return $query;
    }

    /**
     * @return string|null
     */
    public function getEventLocaleAttribute(): ?string
    {
        return trans('events/' . $this->loggable_type . '.' . $this->event) ?? null;
    }
}
