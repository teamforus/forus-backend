<?php

namespace App\Modules\DCP\Enums;

enum DCPSubmissionOutcome: string
{
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case UNKNOWN = 'unknown';
}
