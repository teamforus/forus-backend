<?php

namespace App\Notifications\Identities\Fund;

/**
 * Notify requester that a new fund provider was approved to scan any budget vouchers
 */
class IdentityRequesterProviderApprovedBudgetNotification extends BaseIdentityFundNotification
{
    protected static ?string $key = 'notifications_identities.requester_provider_approved_budget';
}
