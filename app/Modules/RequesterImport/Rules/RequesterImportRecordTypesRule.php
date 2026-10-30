<?php

namespace App\Modules\RequesterImport\Rules;

use App\Models\RecordType;
use App\Modules\RequesterImport\Validation\RequesterImportValidationRules;
use App\Rules\Base\IbanRule;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RequesterImportRecordTypesRule implements ValidationRule
{
    /**
     * @param Collection<string, RecordType> $recordTypes
     * @param bool $batch
     */
    public function __construct(protected Collection $recordTypes, protected bool $batch = true)
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
        foreach ($this->batch ? $value : [$value] as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $prefix = $this->batch ? $attribute . '.' . $index : $attribute;

            foreach ($row as $key => $recordValue) {
                if ($key === 'bsn') {
                    continue;
                }

                $recordType = $this->recordTypes->get($key);

                if (!$recordType) {
                    $fail($prefix . '.' . $key, trans(
                        'requester_import::validation.record_type_not_configured',
                        ['key' => $key],
                    ));

                    continue;
                }

                foreach ($this->validateValue($recordType, $recordValue) as $message) {
                    $fail($prefix . '.' . $key, $message);
                }
            }
        }
    }

    /**
     * @param RecordType $recordType
     * @param mixed $value
     * @return string[]
     */
    protected function validateValue(RecordType $recordType, mixed $value): array
    {
        if (!RequesterImportValidationRules::supportsType(
            $recordType->requester_import_validation_key,
            $recordType->type,
        )) {
            return [trans('requester_import::validation.validation_preset_incompatible', [
                'key' => $recordType->key,
                'preset' => $recordType->requester_import_validation_key?->value,
                'type' => $recordType->type,
            ])];
        }

        $validator = Validator::make(['value' => $value], [
            'value' => 'bail|nullable|string|max:400',
        ], attributes: ['value' => $recordType->key]);

        if ($validator->fails() || $value === null) {
            return $validator->errors()->all();
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

        return Validator::make(['value' => $value], [
            'value' => [
                ...$rules,
                ...RequesterImportValidationRules::rules($recordType->requester_import_validation_key),
            ],
        ], attributes: ['value' => $recordType->key])->errors()->all();
    }
}
