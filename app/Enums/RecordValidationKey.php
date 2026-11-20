<?php

namespace App\Enums;

enum RecordValidationKey: string
{
    case PERSON_NAME = 'person_name';
    case BIRTH_DATE = 'birth_date';
    case SHORT_TEXT = 'short_text';
    case CITY_NAME = 'city_name';
    case STREET_NAME = 'street_name';
    case HOUSE_NUMBER = 'house_number';
    case HOUSE_ADDITION = 'house_addition';
    case POSTCODE = 'postcode';
    case NAME_PART = 'name_part';
    case BSN = 'bsn';
    case HOUSE_LETTER = 'house_letter';
}
