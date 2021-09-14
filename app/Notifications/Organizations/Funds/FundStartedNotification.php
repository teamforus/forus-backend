<?php

namespace App\Notifications\Organizations\Funds;

/**
 * Class FundStartedNotification
 * @package App\Notifications\Organizations\Funds
 */
class FundStartedNotification extends BaseFundsNotification {
    protected static $key = 'notifications_funds.started';
    protected static $permissions = 'view_funds';
}
