<?php

namespace App\Notifications\Organizations\FundProviders;

/**
 * Class FundProviderSponsorChatMessageNotification
 * @package App\Notifications\Organizations\FundProviders
 */
class FundProviderSponsorChatMessageNotification extends BaseFundProvidersNotification
{
    protected $key = 'notifications_fund_providers.sponsor_message';
    protected static $permissions = [
        'manage_provider_funds'
    ];
}
