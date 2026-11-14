<?php

namespace App\Modules\DCP\Enums;

enum DCPEventType: string
{
    case CREATED = 'created';
    case UPDATED = 'updated';
    case BULK_APPROVED = 'approved';
    case SENDING = 'sending';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case UNKNOWN = 'unknown';
    case BLOCKED = 'blocked';
    case STATUS_CHANGED = 'status_changed';
}
