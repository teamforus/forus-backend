<?php

namespace App\Notifications\Organizations\Funds;

/**
 * Class FundProviderChatMessageNotification
 * @package App\Notifications\Organizations\Funds
 */
class FundProviderChatMessageNotification extends BaseFundsNotification {
    protected $key = 'notifications_funds.provider_message';
    protected static $permissions = [
        'manage_providers'
    ];
}
