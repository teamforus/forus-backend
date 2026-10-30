<?php

namespace App\Modules\RequesterImport\Enums;

enum RequesterImportValidationKey: string
{
    case PERSON_NAME = 'person_name';
    case BIRTH_DATE = 'birth_date';
}
