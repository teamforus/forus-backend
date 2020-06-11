<?php

namespace App\Notifications\Organizations\FundProviders;

/**
 * Class FundProvidersRevokedBudgetNotification
 * @package App\Notifications\Organizations\FundProviders
 */
class FundProvidersRevokedBudgetNotification extends BaseFundProvidersNotification
{
    protected $key = 'notifications_fund_providers.revoked_budget';
    protected static $permissions = [
        'manage_provider_funds'
    ];
}
