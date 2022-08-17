<?php

namespace App\Notifications\Organizations\Funds;

class FundProductSubsidyRemovedNotification extends BaseFundsNotification {
    protected static ?string $key = 'notifications_funds.product_subsidy_removed';
    protected static string|array $permissions = 'manage_providers';
}
