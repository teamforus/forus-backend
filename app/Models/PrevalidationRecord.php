<?php

namespace App\Models;

use App\Services\Forus\Record\Models\RecordType;
use Carbon\Carbon;

/**
 * Class PrevalidationRecord
 * @property int $id
 * @property int $record_type_id
 * @property string $value
 * @property RecordType $record_type
 * @property Prevalidation $prevalidation
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
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
