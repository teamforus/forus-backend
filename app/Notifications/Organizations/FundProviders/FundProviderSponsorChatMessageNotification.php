<?php

namespace App\Notifications\Organizations\FundProviders;

use App\Models\Permission;

/**
 * Notify the provider that a new chat message from the sponsor was received.
 */
class FundProviderSponsorChatMessageNotification extends BaseFundProvidersNotification
{
    protected static ?string $key = 'notifications_fund_providers.sponsor_message';
    protected static string|array $permissions = Permission::MANAGE_PROVIDER_FUNDS;
}
