<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\PrevalidationRecord
 *
 * @property int $id
 * @property int $record_type_id
 * @property int $prevalidation_id
 * @property string $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Prevalidation $prevalidation
 * @property-read \App\Models\RecordType $record_type
 * @method static \Illuminate\Database\Eloquent\Builder|PrevalidationRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PrevalidationRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PrevalidationRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder|PrevalidationRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PrevalidationRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PrevalidationRecord wherePrevalidationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PrevalidationRecord whereRecordTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PrevalidationRecord whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PrevalidationRecord whereValue($value)
 * @mixin \Eloquent
 */
class PrevalidationRecord extends BaseModel
{
    /**
     * @var array
     */
    protected $fillable = [
        'record_type_id', 'value'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function prevalidation(): BelongsTo
    {
        return $this->belongsTo(Prevalidation::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function record_type(): BelongsTo
    {
        return $this->belongsTo(RecordType::class);
    }

    /**
     * @param Identity $identity
     * @return Record
     */
    public function makeRecord(Identity $identity): Record
    {
        return $identity->makeRecord($this->record_type, $this->value)->updateModel([
            'prevalidation_id' => $this->prevalidation_id
        ]);
    }
}
