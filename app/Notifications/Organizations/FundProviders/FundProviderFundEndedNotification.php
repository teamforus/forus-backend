<?php

namespace App\Notifications\Organizations\FundProviders;

/***
 * Notify provider that a fund they supplied ended
 */
class FundProviderFundEndedNotification extends BaseFundProvidersNotification
{
    protected static ?string $key = 'notifications_fund_providers.fund_ended';
    protected static string|array $permissions = 'manage_provider_funds';
}
