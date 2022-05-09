<?php

namespace App\Notifications\Organizations\Funds;

/**
 * Notify sponsor that the fund has is expiring
 */
class FundExpiringNotification extends BaseFundsNotification
{
    protected static ?string $key = 'notifications_funds.expiring';
    protected static $permissions = 'view_funds';
}
