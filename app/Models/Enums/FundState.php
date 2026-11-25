<?php

namespace App\Models\Enums;

use App\Models\Fund;

enum FundState: string
{
    case Active = Fund::STATE_ACTIVE;
    case Closed = Fund::STATE_CLOSED;
    case Paused = Fund::STATE_PAUSED;
    case Waiting = Fund::STATE_WAITING;

    /**
     * @param Fund $fund
     * @return self
     */
    public static function forFund(Fund $fund): self
    {
        return self::from($fund->state);
    }
}
