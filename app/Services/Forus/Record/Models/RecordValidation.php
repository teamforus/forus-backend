<?php

namespace App\Services\Forus\Record\Models;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Services\Forus\Record\Models\RecordValidation
 *
 * @property int $id
 * @property string $uuid
 * @property int $record_id
 * @property string|null $identity_address
 * @property int|null $organization_id
 * @property string $state
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Organization|null $organization
 * @property-read \App\Services\Forus\Record\Models\Record $record
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\RecordValidation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\RecordValidation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\RecordValidation query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\RecordValidation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\RecordValidation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\RecordValidation whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\RecordValidation whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\RecordValidation whereRecordId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\RecordValidation whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\RecordValidation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\RecordValidation whereUuid($value)
 * @mixin \Eloquent
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
