<?php

namespace App\Modules\VoucherPass\Enums;

enum VoucherPassDCPStatus: string
{
    case WOULD_CREATE = 'would_create';
    case WOULD_UPDATE = 'would_update';
    case CREATED = 'created';
    case UPDATED = 'updated';
    case UNCHANGED = 'unchanged';
    case SKIPPED = 'skipped';
    case BLOCKED = 'blocked';
    case FAILED = 'failed';
}
