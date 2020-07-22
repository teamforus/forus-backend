<?php

namespace App\Services\EventLogService\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * App\Services\EventLogService\Models\Digest
 *
 * @property int $id
 * @property string $type
 * @property string $digestable_type
 * @property int $digestable_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $digestable
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\EventLogService\Models\Digest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\EventLogService\Models\Digest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\EventLogService\Models\Digest query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\EventLogService\Models\Digest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\EventLogService\Models\Digest whereDigestableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\EventLogService\Models\Digest whereDigestableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\EventLogService\Models\Digest whereEventLogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\EventLogService\Models\Digest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\EventLogService\Models\Digest whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\EventLogService\Models\Digest whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Digest extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'type',
    ];

    /**
     * @return MorphTo
     */
    public function digestable(): MorphTo
    {
        return $this->morphTo();
    }
}
