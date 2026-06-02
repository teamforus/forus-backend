<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $event_log_id
 * @property int|null $fund_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventLogRelation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventLogRelation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventLogRelation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventLogRelation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventLogRelation whereEventLogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventLogRelation whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventLogRelation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventLogRelation whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class EventLogRelation extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'event_log_id', 'fund_id',
    ];
}
