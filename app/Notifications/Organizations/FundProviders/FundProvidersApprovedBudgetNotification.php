<?php

namespace App\Notifications\Organizations\FundProviders;

/**
 * Class FundProvidersApprovedBudgetNotification
 * @package App\Notifications\Organizations\FundProviders
 */
class FundProvidersApprovedBudgetNotification extends BaseFundProvidersNotification
{
    protected $key = 'notifications_fund_providers.approved_budget';
    protected static $permissions = [
        'manage_provider_funds'
    ];
}
