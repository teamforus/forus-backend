<?php

namespace App\Notifications\Organizations\Funds;

/**
 * Notify sponsor that they have a new product chat message from a provider
 */
class FundProviderChatMessageNotification extends BaseFundsNotification
{
    protected static ?string $key = 'notifications_funds.provider_message';
    protected static $permissions = 'manage_providers';
}
