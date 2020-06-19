<?php

namespace App\Notifications\Organizations\Funds;

/**
 * Class FundEndedNotification
 * @package App\Notifications\Organizations\Funds
 */
class FundEndedNotification extends BaseFundsNotification {
    protected $key = 'notifications_funds.ended';
    protected static $permissions = [
        'view_funds'
    ];
}
