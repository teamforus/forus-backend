<?php

namespace App\Notifications\Organizations\FundProviders;

/***
 * Notify provider that a fund they supplied ended
 */
class FundProviderFundEndedNotification extends BaseFundProvidersNotification
{
    protected static $key = 'notifications_fund_providers.fund_ended';
    protected static $permissions = 'manage_provider_funds';
}
