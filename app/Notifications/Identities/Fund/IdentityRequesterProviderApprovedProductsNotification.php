<?php

namespace App\Notifications\Identities\Fund;

/**
 * Notify that the provider was approved to sell all their products
 */
class IdentityRequesterProviderApprovedProductsNotification extends BaseIdentityFundNotification
{
    protected static ?string $key = 'notifications_identities.requester_provider_approved_products';
}
