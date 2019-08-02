<?php

namespace App\Services\Forus\Record\Models;

use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class RecordValidation
 * @property mixed $id
 * @property string $uuid
 * @property string $identity_address
 * @property integer $record_id
 * @property integer $organization_id
 * @property string $state
 * @property Record $record
 * @property Organization $organization
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
        'identity_address', 'record_id', 'state', 'uuid', 'organization_id',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function record() {
        return $this->belongsTo(Record::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization() {
        return $this->belongsTo(Organization::class)->select([
            'id', 'name', 'email',
        ]);
    }
}
