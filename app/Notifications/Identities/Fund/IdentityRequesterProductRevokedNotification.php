<?php

namespace App\Notifications\Identities\Fund;

/**
 * Notify that a product is no longer allowed by the sponsor
 */
class IdentityRequesterProductRevokedNotification extends BaseIdentityFundNotification
{
    protected static $key = 'notifications_identities.requester_product_revoked';
    protected static $scope = null;
}
