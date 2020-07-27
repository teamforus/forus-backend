<?php

namespace App\Notifications\Organizations\Funds;

/**
 * Class FundProductAddedNotification
 * @package App\Notifications\Organizations\Funds
 */
class FundProductAddedNotification extends BaseFundsNotification {
    protected $key = 'notifications_funds.product_added';
    protected static $permissions = [
        'manage_providers'
    ];
}
