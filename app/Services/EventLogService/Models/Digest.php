<?php

namespace App\Services\EventLogService\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * App\Services\EventLogService\Models\Digest.
 *
 * @property int $id
 * @property string $type
 * @property string $digestable_type
 * @property int $digestable_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model|\Eloquent $digestable
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Digest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Digest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Digest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Digest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Digest whereDigestableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Digest whereDigestableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Digest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Digest whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Digest whereUpdatedAt($value)
 * @mixin Eloquent
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
