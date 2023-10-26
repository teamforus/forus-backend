<?php

namespace App\Rules\FundRequests\RecordTypes;

use App\Helpers\Validation;
use App\Models\FundCriterion;
use App\Rules\BaseRule;
use Illuminate\Support\Facades\Log;

abstract class BaseRecordTypeRule extends BaseRule
{
    /**
     * @var string
     */
    protected string $dateFormat = 'd-m-Y';

    /**
     * @param FundCriterion $criterion
     */
    public function __construct(protected FundCriterion $criterion) {}

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, mixed $value): bool
    {
        if (!$this->checkCriterionValidity($this->criterion)) {
            return $this->reject(trans('validation.in', compact('attribute')));
        }

        $validator = Validation::check($value, array_filter($this->rules()));

        return $validator->passes() || $this->reject($validator->errors()->first('value'));
    }

    abstract function rules(): array;

    /**
     * @return string
     */
    protected function isRequiredRule(): string
    {
        return $this->criterion->optional ? 'nullable' : 'required';
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
}
