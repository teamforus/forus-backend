<?php

namespace App\Notifications\Organizations\Funds;

/**
 * Class FundProductAddedNotification
 * @package App\Notifications\Organizations\Funds
 */
class FundProductSubsidyRemovedNotification extends BaseFundsNotification {
    protected static ?string $key = 'notifications_funds.product_subsidy_removed';
    protected static $permissions = 'manage_providers';
}
