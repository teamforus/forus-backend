<?php

namespace App\Notifications\Identities\Fund;

/**
 * Notify that the provider was manually approved by the sponsor to sell a product
 */
class IdentityRequesterProductApprovedNotification extends BaseIdentityFundNotification
{
    protected static ?string $key = 'notifications_identities.requester_product_approved';
}
