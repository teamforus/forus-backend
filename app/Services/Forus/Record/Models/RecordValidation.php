<?php

namespace App\Services\Forus\Record\Models;

use App\Models\Organization;
use App\Models\Prevalidation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Services\Forus\Record\Models\RecordValidation
 *
 * @property int $id
 * @property string $uuid
 * @property int $record_id
 * @property string|null $identity_address
 * @property int|null $organization_id
 * @property int|null $prevalidation_id
 * @property string $state
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \Illuminate\Support\Carbon|null $validation_date
 * @property-read Organization|null $organization
 * @property-read Prevalidation|null $prevalidation
 * @property-read \App\Services\Forus\Record\Models\Record $record
 * @method static \Illuminate\Database\Eloquent\Builder|RecordValidation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RecordValidation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RecordValidation query()
 * @method static \Illuminate\Database\Eloquent\Builder|RecordValidation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecordValidation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecordValidation whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecordValidation whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecordValidation wherePrevalidationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecordValidation whereRecordId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecordValidation whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecordValidation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecordValidation whereUuid($value)
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
        'identity_address', 'record_id', 'state', 'uuid',
        'organization_id', 'prevalidation_id',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function record(): BelongsTo
    {
        return $this->belongsTo(Record::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class)->select([
            'id', 'name', 'email',
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function prevalidation(): BelongsTo
    {
        return $this->belongsTo(Prevalidation::class);
    }

    /**
     * @return \Illuminate\Support\Carbon|null
     * @noinspection PhpUnused
     */
    public function getValidationDateAttribute(): ?Carbon {
        return $this->prevalidation_id ? $this->prevalidation->validated_at : $this->created_at;
    }
}
