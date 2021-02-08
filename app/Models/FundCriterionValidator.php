<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\FundCriterionValidator
 *
 * @property int $id
 * @property int $fund_criterion_id
 * @property int $organization_validator_id
 * @property bool $accepted
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OrganizationValidator $external_validator
 * @property-read \App\Models\FundCriterion $fund_criterion
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterionValidator newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterionValidator newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterionValidator query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterionValidator whereAccepted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterionValidator whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterionValidator whereFundCriterionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterionValidator whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterionValidator whereOrganizationValidatorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterionValidator whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundCriterionValidator extends Model
{
    protected $fillable = [
        'fund_criterion_id', 'organization_validator_id', 'accepted'
    ];

    protected $casts = [
        'accepted' => 'boolean'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function external_validator(): BelongsTo {
        return $this->belongsTo(OrganizationValidator::class, 'organization_validator_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fund_criterion(): BelongsTo {
        return $this->belongsTo(FundCriterion::class);
    }
}
