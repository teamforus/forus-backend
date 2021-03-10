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
 * @property-read Model|\Eloquent $digestable
 * @method static \Illuminate\Database\Eloquent\Builder|Digest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Digest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Digest query()
 * @method static \Illuminate\Database\Eloquent\Builder|Digest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Digest whereDigestableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Digest whereDigestableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Digest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Digest whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Digest whereUpdatedAt($value)
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
