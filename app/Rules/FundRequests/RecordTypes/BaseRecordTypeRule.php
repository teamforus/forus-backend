<?php

namespace App\Rules\FundRequests\RecordTypes;

use App\Helpers\Validation;
use App\Models\FundCriterion;
use App\Rules\BaseRule;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

abstract class BaseRecordTypeRule extends BaseRule
{
    /**
     * @var string
     */
    protected string $dateFormat = 'd-m-Y';

    /**
     * @param FundCriterion $criterion
     */
    public function __construct(protected FundCriterion $criterion, protected ?string $labelOverride = null)
    {
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, mixed $value): bool
    {
        $label = $this->attributeLabel();

        if (!$this->checkCriterionValidity($this->criterion)) {
            return $this->reject(__('validation.in', [$attribute => $label]));
        }

        $validator = Validation::checkWithLabels(
            $attribute,
            $value,
            array_filter($this->rules()),
            attributes: [$attribute => $label],
        );

        return $validator->passes() || $this->reject($validator->errors()->first($attribute));
    }

    abstract public function rules(): array;

    /**
     * @return string
     */
    protected function isRequiredRule(): string
    {
        return $this->criterion->optional ? 'nullable' : 'required';
    }

    /**
     * @return \Illuminate\Validation\Rules\In|string|null
     */
    protected function getLengthRule(): \Illuminate\Validation\Rules\In|string|null
    {
        return match($this->criterion->operator) {
            '=' => Rule::in([$this->criterion->value]),
            '>' => is_numeric($this->criterion->value) ? ("gt:{$this->criterion->value}") : 'in:',
            '<' => is_numeric($this->criterion->value) ? ("lt:{$this->criterion->value}") : 'in:',
            '>=' => is_numeric($this->criterion->value) ? ("gte:{$this->criterion->value}") : 'in:',
            '<=' => is_numeric($this->criterion->value) ? ("lte:{$this->criterion->value}") : 'in:',
            '*' => null,
            default => Rule::in([]),
        };
    }

    /**
     * @param FundCriterion $criterion
     * @return bool
     */
    protected function checkCriterionValidity(FundCriterion $criterion): bool
    {
        return
            $this->checkCriterionRangeValidity($criterion) &&
            $this->checkCriterionOperatorValidity($criterion);
    }

    /**
     * @param FundCriterion $criterion
     * @return bool
     */
    protected function checkCriterionOperatorValidity(FundCriterion $criterion): bool
    {
        $typeDate = $criterion->record_type->type == $criterion->record_type::TYPE_DATE;

        if (!in_array($criterion->operator, $criterion->record_type->getOperators())) {
            Log::error("Invalid criteria operator detected [$criterion->id].");

            return false;
        }

        if (in_array($criterion->operator, ['>', '<'], true)) {
            if ($typeDate && !$this->isValidDate($criterion->value)) {
                Log::error("Invalid criteria value detected [$criterion->id].");

                return false;
            }

            if (!$typeDate && !is_numeric($criterion->value)) {
                Log::error("Invalid criteria value detected [$criterion->id].");

                return false;
            }
        }

        return true;
    }

    /**
     * @param FundCriterion $criterion
     * @return bool
     */
    protected function checkCriterionRangeValidity(FundCriterion $criterion): bool
    {
        $typeDate = $criterion->record_type->type == $criterion->record_type::TYPE_DATE;

        if ($criterion->min && ($typeDate ? !$this->isValidDate($criterion->min) : !is_numeric($criterion->min))) {
            Log::error("Invalid criteria min value detected [$criterion->id].");

            return false;
        }

        if ($criterion->max && ($typeDate ? !$this->isValidDate($criterion->max) : !is_numeric($criterion->max))) {
            Log::error("Invalid criteria max value detected [$criterion->id].");

            return false;
        }

        return true;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    protected function isValidDate(mixed $value): bool
    {
        return Validation::check($value, "required|date|date_format:$this->dateFormat")->passes();
    }

    /**
     * @return string
     */
    protected function attributeLabel(): string {
        return $this->labelOverride
            ?? $this->criterion->recordType->translation->name
            ?? trans('validation.attributes.value');
    }
}
