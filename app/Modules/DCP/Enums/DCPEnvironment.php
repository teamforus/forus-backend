<?php

namespace App\Modules\DCP\Enums;

enum DCPEnvironment: string
{
    case SANDBOX = 'sandbox';
    case PRODUCTION = 'production';
}
