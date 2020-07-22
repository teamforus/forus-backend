<?php

namespace App\Notifications\Organizations\Funds;

/**
 * Class BalanceSuppliedNotification
 * @package App\Notifications\Organizations\Funds
 */
class BalanceSuppliedNotification extends BaseFundsNotification
{
    protected $key = 'notifications_funds.balance_supplied';
    protected static $permissions = [
        'view_finances'
    ];
}
