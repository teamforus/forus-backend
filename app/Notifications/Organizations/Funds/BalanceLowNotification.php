<?php

namespace App\Notifications\Organizations\Funds;

/**
 * Class BalanceLowNotification
 * @package App\Notifications\Organizations\Funds
 */
class BalanceLowNotification extends BaseFundsNotification {
    protected $key = 'notifications_funds.balance_low';

    protected static $permissions = [
        'view_finances'
    ];
}
