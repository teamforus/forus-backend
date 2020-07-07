<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\OrganizationValidator
 *
 * @property int $id
 * @property int $organization_id
 * @property int $validator_organization_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Organization $organization
 * @property-read \App\Models\Organization $validator_organization
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrganizationValidator newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrganizationValidator newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrganizationValidator query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrganizationValidator whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrganizationValidator whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrganizationValidator whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrganizationValidator whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrganizationValidator whereValidatorOrganizationId($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundCriterionValidator[] $fund_criteria_validators
 * @property-read int|null $fund_criteria_validators_count
 */
class OrganizationValidator extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'organization_id', 'validator_organization_id',
    ];

    /**
     * @return HasMany
     */
    public function fund_criteria_validators(): HasMany {
        return $this->hasMany(FundCriterionValidator::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function validator_organization(): BelongsTo {
        return $this->belongsTo(Organization::class, 'validator_organization_id');
    }
}
