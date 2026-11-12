<?php

namespace App\Validation;

use App\Enums\RecordValidationKey;
use App\Models\RecordType;
use App\Rules\BsnRule;
use Illuminate\Contracts\Validation\Rule;

class RecordValidationRules
{
    /**
     * @param RecordValidationKey|null $key
     * @return array<int, string|Rule>
     */
    public static function rules(?RecordValidationKey $key): array
    {
        return match ($key) {
            RecordValidationKey::PERSON_NAME, RecordValidationKey::SHORT_TEXT => ['min:2', 'max:40'],
            RecordValidationKey::BIRTH_DATE => ['before_or_equal:today'],
            RecordValidationKey::CITY_NAME => ['city_name'],
            RecordValidationKey::STREET_NAME => ['street_name'],
            RecordValidationKey::HOUSE_NUMBER => ['house_number'],
            RecordValidationKey::HOUSE_ADDITION => ['house_addition'],
            RecordValidationKey::POSTCODE => ['postcode'],
            RecordValidationKey::NAME_PART => ['min:1', 'max:40'],
            RecordValidationKey::BSN => [new BsnRule(exactlyNineDigits: true)],
            RecordValidationKey::HOUSE_LETTER => ['regex:/^[A-Za-z]$/'],
            null => [],
        };
    }

    /**
     * @param RecordValidationKey|null $key
     * @param string $type
     * @return bool
     */
    public static function supportsType(?RecordValidationKey $key, string $type): bool
    {
        return match ($key) {
            RecordValidationKey::PERSON_NAME,
            RecordValidationKey::SHORT_TEXT,
            RecordValidationKey::CITY_NAME,
            RecordValidationKey::STREET_NAME,
            RecordValidationKey::HOUSE_NUMBER,
            RecordValidationKey::HOUSE_ADDITION,
            RecordValidationKey::POSTCODE,
            RecordValidationKey::NAME_PART,
            RecordValidationKey::BSN,
            RecordValidationKey::HOUSE_LETTER => $type === RecordType::TYPE_STRING,
            RecordValidationKey::BIRTH_DATE => $type === RecordType::TYPE_DATE,
            null => true,
        };
    }
}
