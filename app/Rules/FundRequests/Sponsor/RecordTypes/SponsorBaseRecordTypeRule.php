<?php

namespace App\Rules\FundRequests\Sponsor\RecordTypes;

use App\Helpers\Validation;
use App\Models\RecordType;
use App\Rules\BaseRule;

abstract class SponsorBaseRecordTypeRule extends BaseRule
{
    /**
     * @param RecordType $recordType
     * @param string|null $label
     */
    public function __construct(
        protected RecordType $recordType,
        protected ?string $label = null,
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
        $label = $this->attributeLabel();

        $validator = Validation::check($value, array_filter($this->rules()), $label);

        return $validator->passes() || $this->reject($validator->errors()->first('value'));
    }

    abstract public function rules(): array;

    /**
     * @return string
     */
    protected function attributeLabel(): string
    {
        return $this->label
            ?? $this->recordType->name
            ?? trans('validation.attributes.value');
    }
}
