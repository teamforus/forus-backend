<?php

namespace App\Notifications\Organizations\Funds;

/**
 * Notify sponsor that the fund has started
 */
class FundStartedNotification extends BaseFundsNotification
{
    protected static $key = 'notifications_funds.started';
    protected static $permissions = 'view_funds';
}
