<?php

namespace App\Notifications\Organizations\Funds;

/**
 * Class FundExpiringNotification
 * @package App\Notifications\Organizations\Funds
 */
class FundExpiringNotification extends BaseFundsNotification {
    protected static $key = 'notifications_funds.expiring';
    protected static $permissions = 'view_funds';
}
