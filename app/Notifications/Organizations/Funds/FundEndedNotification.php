<?php

namespace App\Notifications\Organizations\Funds;

/**
 * Notify sponsor that the fund has ended
 */
class FundEndedNotification extends BaseFundsNotification
{
    protected static ?string $key = 'notifications_funds.ended';
    protected static $permissions = 'view_funds';
}
