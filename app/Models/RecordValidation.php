<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\RecordValidation
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
 * @property-read \App\Models\Identity|null $identity
 * @property-read \App\Models\Organization|null $organization
 * @property-read \App\Models\Prevalidation|null $prevalidation
 * @property-read \App\Models\Record $record
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
    public const STATE_PENDING = 'pending';
    public const STATE_APPROVED = 'approved';
    public const STATE_DECLINED = 'declined';

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
     * @return bool
     * @noinspection PhpUnused
     */
    private function isPending(): bool
    {
        return $this->state === self::STATE_PENDING;
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function isDeclined(): bool
    {
        return $this->state === self::STATE_DECLINED;
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function isApproved(): bool
    {
        return $this->state === self::STATE_APPROVED;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'identity_address', 'address');
    }

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
    public function getValidationDateAttribute(): ?Carbon
    {
        return $this->prevalidation_id ? $this->prevalidation->validated_at : $this->created_at;
    }

    /**
     * @param string $uuid
     * @return RecordValidation|null
     */
    public static function findByUuid(string $uuid): ?RecordValidation
    {
        return static::whereUuid($uuid)->first();
    }

    /**
     * @param Identity $identity
     * @return bool
     */
    public function decline(Identity $identity): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        return $this->update([
            'identity_address' => $identity->address,
            'state' => self::STATE_DECLINED,
        ]);
    }

    /**
     * @param Identity $identity
     * @param Organization|null $organization
     * @param Prevalidation|null $prevalidation
     * @return bool
     */
    public function approve(
        Identity $identity,
        ?Organization $organization = null,
        ?Prevalidation $prevalidation = null
    ): bool {
        if (!$this->isPending() || $this->identity) {
            return false;
        }

        return $this->update([
            'state' => self::STATE_APPROVED,
            'organization_id' => $organization?->id,
            'prevalidation_id' => $prevalidation?->id,
            'identity_address' => $identity->address,
        ]);
    }
}
