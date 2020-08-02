<?php

namespace App\Models;

/**
 * App\Models\FundCriterion
 *
 * @property int $id
 * @property int $fund_id
 * @property string $record_type_key
 * @property string $operator
 * @property string $value
 * @property bool $show_attachment
 * @property string $description
 * @property string $title
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund $fund
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion whereOperator($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion whereRecordTypeKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion whereShowAttachment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion whereValue($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundCriterionValidator[] $fund_criterion_validators
 * @property-read int|null $fund_criterion_validators_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Organization[] $external_validator_organizations
 * @property-read int|null $external_validator_organizations_count
 */
class FundCriterion extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'fund_id', 'record_type_key', 'operator', 'value',
        'show_attachment', 'description', 'title'
    ];

    protected $casts = [
        'show_attachment' => 'boolean'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function fund() {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function fund_criterion_validators() {
        return $this->hasMany(FundCriterionValidator::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    function external_validator_organizations() {
        return $this->belongsToMany(OrganizationValidator::class, FundCriterionValidator::class);
    }
}
