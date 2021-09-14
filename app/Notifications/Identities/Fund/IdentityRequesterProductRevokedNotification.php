<?php

namespace App\Notifications\Identities\Fund;

class IdentityRequesterProductRevokedNotification extends BaseIdentityFundNotification
{
    protected static $key = 'notifications_identities.requester_product_revoked';
    protected static $scope = null;
    protected static $visible = true;
}
