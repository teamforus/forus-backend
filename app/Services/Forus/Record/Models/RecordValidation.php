<?php

namespace App\Services\Forus\Record\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class RecordValidation
 * @property mixed $id
 * @property string $uuid
 * @property string $identity_address
 * @property integer $record_id
 * @property string $state
 * @property Record $record
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
 */
class RecordValidation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'identity_address', 'record_id', 'state', 'uuid'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function record() {
        return $this->belongsTo(Record::class);
    }
}
