<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 *
 *
 * @property int $id
 * @property string $record_type_key
 * @property int $prevalidation_request_id
 * @property string $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\PrevalidationRequest $prevalidation_request
 * @property-read \App\Models\RecordType $record_type
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequestRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequestRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequestRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequestRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequestRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequestRecord wherePrevalidationRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequestRecord whereRecordTypeKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequestRecord whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequestRecord whereValue($value)
 * @mixin \Eloquent
 */
class PrevalidationRequestRecord extends BaseModel
{
    /**
     * @var array
     */
    protected $fillable = [
        'record_type_key', 'value',
    ];

    /**
     * @return BelongsTo
     */
    public function prevalidation_request(): BelongsTo
    {
        return $this->belongsTo(PrevalidationRequest::class);
    }

    /**
     * @return BelongsTo
     */
    public function record_type(): BelongsTo
    {
        return $this->belongsTo(RecordType::class, 'record_type_key', 'key');
    }
}
