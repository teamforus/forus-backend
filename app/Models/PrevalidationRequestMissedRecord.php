<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $type
 * @property string $group
 * @property string $field
 * @property int $prevalidation_request_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequestMissedRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequestMissedRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequestMissedRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequestMissedRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequestMissedRecord whereField($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequestMissedRecord whereGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequestMissedRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequestMissedRecord wherePrevalidationRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequestMissedRecord whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequestMissedRecord whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PrevalidationRequestMissedRecord extends Model
{
    public const string TYPE_INFO = 'info';
    public const string TYPE_WARNING = 'warning';

    protected $fillable = [
        'prevalidation_request_id', 'group', 'field', 'type',
    ];
}
