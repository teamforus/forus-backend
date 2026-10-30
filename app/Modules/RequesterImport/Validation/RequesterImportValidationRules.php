<?php

namespace App\Modules\RequesterImport\Validation;

use App\Models\RecordType;
use App\Modules\RequesterImport\Enums\RequesterImportValidationKey;

class RequesterImportValidationRules
{
    /**
     * @param RequesterImportValidationKey|null $key
     * @return string[]
     */
    public static function rules(?RequesterImportValidationKey $key): array
    {
        return match ($key) {
            RequesterImportValidationKey::PERSON_NAME => ['min:2', 'max:40'],
            RequesterImportValidationKey::BIRTH_DATE => ['before_or_equal:today'],
            null => [],
        };
    }

    /**
     * @param RequesterImportValidationKey|null $key
     * @param string $type
     * @return bool
     */
    public static function supportsType(?RequesterImportValidationKey $key, string $type): bool
    {
        return match ($key) {
            RequesterImportValidationKey::PERSON_NAME => $type === RecordType::TYPE_STRING,
            RequesterImportValidationKey::BIRTH_DATE => $type === RecordType::TYPE_DATE,
            null => true,
        };
    }
}
