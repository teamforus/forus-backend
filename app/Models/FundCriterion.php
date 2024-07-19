<?php

namespace App\Models;

use App\Helpers\Markdown;
use App\Helpers\Validation;
use App\Rules\FundRequests\CriterionRules\CriteriaRuleTypeBoolRule;
use App\Rules\FundRequests\CriterionRules\CriteriaRuleTypeDateRule;
use App\Rules\FundRequests\CriterionRules\CriteriaRuleTypeEmailRule;
use App\Rules\FundRequests\CriterionRules\CriteriaRuleTypeIbanRule;
use App\Rules\FundRequests\CriterionRules\CriteriaRuleTypeNumericRule;
use App\Rules\FundRequests\CriterionRules\CriteriaRuleTypeSelectNumberRule;
use App\Rules\FundRequests\CriterionRules\CriteriaRuleTypeSelectRule;
use App\Rules\FundRequests\CriterionRules\CriteriaRuleTypeStringRule;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use League\CommonMark\Exception\CommonMarkException;

/**
 * App\Models\FundCriterion
 *
 * @property int $id
 * @property int $fund_id
 * @property string $record_type_key
 * @property int|null $order
 * @property int|null $fund_criteria_step_id
 * @property string $operator
 * @property string $value
 * @property bool $optional
 * @property string|null $min
 * @property string|null $max
 * @property string|null $title
 * @property bool $show_attachment
 * @property string $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\OrganizationValidator[] $external_validator_organizations
 * @property-read int|null $external_validator_organizations_count
 * @property-read \App\Models\Fund $fund
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundCriterionRule[] $fund_criterion_rules
 * @property-read int|null $fund_criterion_rules_count
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
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriterion whereFundCriteriaStepId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriterion whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriterion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriterion whereMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriterion whereMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriterion whereOperator($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriterion whereOptional($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriterion whereOrder($value)
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
        'description', 'title', 'optional', 'min', 'max', 'order', 'fund_criteria_step_id',
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function fund_criterion_rules(): HasMany
    {
        return $this->hasMany(FundCriterionRule::class);
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

    /**
     * @param array $values
     * @return bool
     */
    public function isExcludedByRules(array $values): bool
    {
        if ($this->fund_criterion_rules->isEmpty()) {
            return false;
        }

        return $this->fund_criterion_rules->filter(function ($rule) use ($values) {
            return $this->validateRecordValue($rule, $values)?->fails();
        })->isNotEmpty();
    }

    /**
     * @param mixed $rule
     * @param array $values
     * @return Validator|null
     */
    public function validateRecordValue(FundCriterionRule $rule, array $values): ?Validator
    {
        $type = record_types_static()[$rule->record_type_key] ?? null;
        $value = $values[$rule->record_type_key] ?? '';

        return match ($type?->type) {
            RecordType::TYPE_STRING => CriteriaRuleTypeStringRule::check($value, $type, $rule),
            RecordType::TYPE_NUMBER => CriteriaRuleTypeNumericRule::check($value, $type, $rule),
            RecordType::TYPE_SELECT => CriteriaRuleTypeSelectRule::check($value, $type, $rule),
            RecordType::TYPE_SELECT_NUMBER => CriteriaRuleTypeSelectNumberRule::check($value, $type, $rule),
            RecordType::TYPE_EMAIL => CriteriaRuleTypeEmailRule::check($value, $type, $rule),
            RecordType::TYPE_IBAN => CriteriaRuleTypeIbanRule::check($value, $type, $rule),
            RecordType::TYPE_BOOL => CriteriaRuleTypeBoolRule::check($value, $type, $rule),
            RecordType::TYPE_DATE => CriteriaRuleTypeDateRule::check($value, $type, $rule),
            default => Validation::check('', ['required', Rule::in([])]),
        };
    }
}
