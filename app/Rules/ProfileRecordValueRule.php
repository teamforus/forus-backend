<?php

namespace App\Rules;

use App\Models\RecordType;
use App\Rules\Base\IbanRule;
use App\Validation\RecordValidationRules;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProfileRecordValueRule implements ValidationRule
{
    /**
     * @param RecordType $recordType
     */
    public function __construct(protected RecordType $recordType)
    {
    }

    /**
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $recordType = $this->recordType;

        if (!RecordValidationRules::supportsType($recordType->validation_key, $recordType->type)) {
            $fail(trans('validation.record_validation_preset_incompatible', [
                'key' => $recordType->key,
                'preset' => $recordType->validation_key?->value,
                'type' => $recordType->type,
            ]));

            return;
        }

        $validator = Validator::make(['value' => $value], [
            'value' => 'bail|nullable|string|max:400',
        ], attributes: ['value' => $recordType->name ?: $recordType->key]);

        if ($validator->fails() || $value === null) {
            foreach ($validator->errors()->all() as $message) {
                $fail($message);
            }

            return;
        }

        $rules = match ($recordType->type) {
            RecordType::TYPE_STRING => [],
            RecordType::TYPE_DATE => ['date_format:Y-m-d'],
            RecordType::TYPE_NUMBER => ['numeric'],
            RecordType::TYPE_EMAIL => ['email'],
            RecordType::TYPE_IBAN => [new IbanRule()],
            RecordType::TYPE_BOOL, RecordType::TYPE_SELECT => [Rule::in(array_column($recordType->getOptions(), 'value'))],
            RecordType::TYPE_SELECT_NUMBER => ['numeric', Rule::in(array_column($recordType->getOptions(), 'value'))],
            default => [Rule::in([])],
        };

        $validator = Validator::make(['value' => $value], [
            'value' => [...$rules, ...RecordValidationRules::rules($recordType->validation_key)],
        ], attributes: ['value' => $recordType->name ?: $recordType->key]);

        foreach ($validator->errors()->all() as $message) {
            $fail($message);
        }
    }
}
