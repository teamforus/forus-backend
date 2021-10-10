<?php

namespace App\Notifications\Organizations\FundProviders;

/**
 * Notify the provider that a new chat message from the sponsor was received
 */
class FundProviderSponsorChatMessageNotification extends BaseFundProvidersNotification
{
    protected static $key = 'notifications_fund_providers.sponsor_message';
    protected static $permissions = 'manage_provider_funds';
}
