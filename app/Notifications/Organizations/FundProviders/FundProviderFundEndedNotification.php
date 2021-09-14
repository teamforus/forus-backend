<?php

namespace App\Notifications\Organizations\FundProviders;

/***
 * Class FundProviderFundEndedNotification
 * @package App\Notifications\Organizations\FundProviders
 */
class FundProviderFundEndedNotification extends BaseFundProvidersNotification
{
    protected static $key = 'notifications_fund_providers.fund_ended';
    protected static $permissions = 'manage_provider_funds';
    protected static $visible = true;
}
