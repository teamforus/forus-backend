<?php

namespace App\Modules\DCP\Enums;

enum DCPOrderState: string
{
    case DRAFT = 'draft';
    case APPROVED = 'approved';
    case SENDING = 'sending';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case UNKNOWN = 'unknown';
    case CLOSED = 'closed';
    case CANCELED = 'canceled';
    case BLOCKED = 'blocked';
}
