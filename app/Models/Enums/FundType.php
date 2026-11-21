<?php

namespace App\Models\Enums;

use App\Models\Fund;

enum FundType: string
{
    case Budget = 'budget';
    case Subsidies = 'subsidies';
    case External = 'external';

    /**
     * @param Fund $fund
     * @return self|null
     */
    public static function forFund(Fund $fund): ?self
    {
        return $fund->type === null ? null : self::from($fund->type);
    }
}
