<?php

namespace App\Models;

use App\Helpers\Markdown;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use League\CommonMark\Exception\CommonMarkException;

/**
 * App\Models\FundCriterion
 *
 * @property int $id
 * @property int $fund_id
 * @property string $record_type_key
 * @property string $operator
 * @property string $value
 * @property bool $optional
 * @property int|null $min
 * @property int|null $max
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
 * @property-read \App\Models\FundRequestRecord|null $fund_request_record
 * @property-read string $description_html
 * @property-read \App\Models\RecordType|null $record_type
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriterion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriterion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriterion query()
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriterion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriterion whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriterion whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriterion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriterion whereMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriterion whereMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriterion whereOperator($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriterion whereOptional($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriterion whereRecordTypeKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriterion whereShowAttachment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriterion whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriterion whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriterion whereValue($value)
 * @mixin \Eloquent
 */
class FundCriterion extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'fund_id', 'record_type_key', 'operator', 'value', 'show_attachment',
        'description', 'title', 'optional', 'min', 'max',
    ];

    protected $casts = [
        'optional' => 'boolean',
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function record_type(): BelongsTo
    {
        return $this->belongsTo(RecordType::class, 'record_type_key', 'key');
    }

    /**
     * @return HasOne
     * @noinspection PhpUnused
     */
    public function fund_request_record(): HasOne
    {
        return $this->hasOne(FundRequestRecord::class);
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
     * @return string
     * @noinspection PhpUnused
     * @throws CommonMarkException
     */
    public function getDescriptionHtmlAttribute(): string
    {
        return Markdown::convert($this->description ?: '');
    }
}
