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
use App\Services\TranslationService\Traits\HasOnDemandTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use League\CommonMark\Exception\CommonMarkException;

/**
 * App\Models\FundCriterion.
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
 * @property string|null $extra_description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $label
 * @property-read \App\Models\Fund $fund
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundCriterionRule[] $fund_criterion_rules
 * @property-read int|null $fund_criterion_rules_count
 * @property-read \App\Models\FundRequestRecord|null $fund_request_record
 * @property-read string $description_html
 * @property-read string $extra_description_html
 * @property-read \App\Models\RecordType|null $record_type
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\TranslationService\Models\TranslationValue[] $translation_values
 * @property-read int|null $translation_values_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundCriterion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundCriterion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundCriterion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundCriterion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundCriterion whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundCriterion whereExtraDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundCriterion whereFundCriteriaStepId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundCriterion whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundCriterion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundCriterion whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundCriterion whereMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundCriterion whereMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundCriterion whereOperator($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundCriterion whereOptional($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundCriterion whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundCriterion whereRecordTypeKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundCriterion whereShowAttachment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundCriterion whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundCriterion whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundCriterion whereValue($value)
 * @mixin \Eloquent
 */
class FundCriterion extends Model
{
    use HasOnDemandTranslations;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'fund_id', 'record_type_key', 'operator', 'value', 'show_attachment', 'label',
        'description', 'title', 'optional', 'min', 'max', 'order', 'fund_criteria_step_id',
        'extra_description',
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
    public function fund_criterion_rules(): HasMany
    {
        return $this->hasMany(FundCriterionRule::class);
    }

    /**
     * @throws CommonMarkException
     * @return string
     * @noinspection PhpUnused
     */
    public function getDescriptionHtmlAttribute(): string
    {
        return Markdown::convert($this->description ?: '');
    }

    /**
     * @throws CommonMarkException
     * @return string
     * @noinspection PhpUnused
     */
    public function getExtraDescriptionHtmlAttribute(): string
    {
        return Markdown::convert($this->extra_description ?: '');
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
