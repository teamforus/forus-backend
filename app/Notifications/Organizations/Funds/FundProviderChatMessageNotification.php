<?php

namespace App\Notifications\Organizations\Funds;

use App\Models\Permission;

/**
 * Notify sponsor that they have a new product chat message from a provider.
 */
class FundProviderChatMessageNotification extends BaseFundsNotification
{
    protected static ?string $key = 'notifications_funds.provider_message';
    protected static string|array $permissions = Permission::MANAGE_PROVIDERS;
}
