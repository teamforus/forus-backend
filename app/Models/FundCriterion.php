<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\FundCriterion
 *
 * @property int $id
 * @property int $fund_id
 * @property string $record_type_key
 * @property string $operator
 * @property string $value
 * @property int|null $records_validity_days
 * @property string|null $title
 * @property bool $show_attachment
 * @property string $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\OrganizationValidator[] $external_validator_organizations
 * @property-read int|null $external_validator_organizations_count
 * @property-read \App\Models\Fund $fund
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundCriterionValidator[] $fund_criterion_validators
 * @property-read int|null $fund_criterion_validators_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion whereOperator($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion whereRecordTypeKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion whereRecordsValidityDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion whereShowAttachment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion whereValue($value)
 * @mixin \Eloquent
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
        'show_attachment', 'description', 'title', 'records_validity_days',
    ];

    protected $casts = [
        'show_attachment' => 'boolean',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function fund_criterion_validators(): HasMany
    {
        return $this->hasMany(FundCriterionValidator::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     * @noinspection PhpUnused
     */
    public function external_validator_organizations(): BelongsToMany
    {
        return $this->belongsToMany(OrganizationValidator::class, FundCriterionValidator::class);
    }

    /**
     * @return int|null
     */
    public function getTrustedDays(): ?int
    {
        return $this->records_validity_days ?:
            ($this->fund->fund_config ? $this->fund->fund_config->records_validity_days : null) ?:
                (int) config('forus.funds.records_validity_days');
    }
}
