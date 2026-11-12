<?php

namespace App\Modules\DCP\Enums;

enum DCPResponseError: string
{
    case TRANSPORT = 'transport';
    case HTTP = 'http';
    case INVALID_RESPONSE = 'invalid_response';
    case REFERENCE_MISMATCH = 'reference_mismatch';
    case DUPLICATE = 'duplicate';
    case NOT_FOUND = 'not_found';
    case REJECTED = 'rejected';
}
