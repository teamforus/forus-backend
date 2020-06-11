<?php

namespace App\Notifications\Organizations\Funds;

/**
 * Class FundProviderAppliedNotification
 * @package App\Notifications\Organizations\Funds
 */
class FundProviderAppliedNotification extends BaseFundsNotification {
    protected $key = 'notifications_funds.provider_applied';
    protected static $permissions = [
        'manage_providers'
    ];
}
