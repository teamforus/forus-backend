<?php

namespace App\Notifications\Organizations\Funds;

/**
 * Class FundCreatedNotification
 * @package App\Notifications\Organizations\Funds
 */
class FundCreatedNotification extends BaseFundsNotification
{
    protected static ?string $key = 'notifications_funds.created';
    protected static $permissions = 'view_funds';
}
