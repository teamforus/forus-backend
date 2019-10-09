<?php

namespace App\Models;

use App\Services\Forus\Record\Models\RecordType;

/**
 * App\Models\PrevalidationRecord
 *
 * @property int $id
 * @property int $record_type_id
 * @property int $prevalidation_id
 * @property string $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
 * @property-read \App\Models\Prevalidation $prevalidation
 * @property-read \App\Services\Forus\Record\Models\RecordType $record_type
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PrevalidationRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PrevalidationRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PrevalidationRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PrevalidationRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PrevalidationRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PrevalidationRecord wherePrevalidationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PrevalidationRecord whereRecordTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PrevalidationRecord whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PrevalidationRecord whereValue($value)
 * @mixin \Eloquent
 */
class PrevalidationRecord extends Model
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
    public function prevalidation() {
        return $this->belongsTo(Prevalidation::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function record_type() {
        return $this->belongsTo(RecordType::class);
    }
}
