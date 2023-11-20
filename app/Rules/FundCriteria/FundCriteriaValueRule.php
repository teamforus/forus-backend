<?php

namespace App\Rules\FundCriteria;

use App\Helpers\Arr;
use App\Helpers\Validation;
use App\Models\RecordType;
use App\Rules\Base\IbanRule;
use Illuminate\Validation\Rule;

class FundCriteriaValueRule extends BaseFundCriteriaRule
{
    /**
     * @var string
     */
    protected string $dateFormat = 'd-m-Y';

    /**
     * @param $attribute
     * @param $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $criterion = $this->getCriteriaRow($attribute);
        $recordType = RecordType::where('key', Arr::get($criterion, 'record_type_key'))->first();

        $min = Arr::get($criterion, 'min');
        $max = Arr::get($criterion, 'max');

        if (!$criterion || !$recordType || !$this->validateMinMax($attribute, $min, $min)->passes()) {
            return $this->reject(trans('validation.in', compact('attribute')));
        }

        $validation = match ($recordType->type) {
            $recordType::TYPE_STRING => Validation::check($value, implode([
                'required|string',
                is_numeric($min) ? "|min:$min" : "",
                is_numeric($max) ? "|max:$max" : "",
            ])),
            $recordType::TYPE_NUMBER => Validation::check($value, implode([
                'required|numeric',
                is_numeric($min) ? "|min:$min" : "",
                is_numeric($max) ? "|max:$max" : "",
            ])),
            $recordType::TYPE_SELECT => Validation::check($value, [
                'nullable',
                Rule::in(Arr::pluck($recordType->getOptions(), 'value')),
            ]),
            $recordType::TYPE_EMAIL => Validation::check($value, ['nullable', 'email']),
            $recordType::TYPE_IBAN => Validation::check($value, ['nullable', new IbanRule()]),
            $recordType::TYPE_BOOL => Validation::check($value, ['nullable', Rule::in(['Ja', 'Nee'])]),
            $recordType::TYPE_DATE => Validation::check($value, implode('', [
                "nullable|date|date_format:$this->dateFormat",
                $this->isValidDate($min) ? "|after_or_equal:$min" : "",
                $this->isValidDate($max) ? "|before_or_equal:$max" : "",
            ])),
            default => Validation::check('', ['required', Rule::in([])]),
        };

        return $validation->passes() || $this->reject($validation->errors()->first('value'));
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
