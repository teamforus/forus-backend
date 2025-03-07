<?php

namespace App\Rules\FundRequests\CriterionRules;

use App\Helpers\Validation;
use App\Models\FundCriterionRule;
use App\Models\RecordType;
use App\Rules\BaseRule;
use Illuminate\Validation\Validator;

abstract class BaseCriteriaRuleTypeRule extends BaseRule
{
    /**
     * @var string
     */
    protected string $dateFormat = 'd-m-Y';

    /**
     * @param RecordType $recordType
     * @param mixed $rule
     */
    public function __construct(
        protected RecordType $recordType,
        protected FundCriterionRule $rule,
    ) {
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
        $validator = Validation::check($value, array_filter($this->rules()));

        return $validator->passes() || $this->reject($validator->errors()->first('value'));
    }

    /**
     * @param mixed $value
     * @param RecordType $recordType
     * @param FundCriterionRule $rule
     * @return Validator
     */
    public static function check(
        mixed $value,
        RecordType $recordType,
        FundCriterionRule $rule,
    ): Validator {
        return Validation::check($value, new static($recordType, $rule));
    }

    abstract public function rules(): array;
}
