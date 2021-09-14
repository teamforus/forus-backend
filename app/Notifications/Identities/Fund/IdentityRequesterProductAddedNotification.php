<?php

namespace App\Notifications\Identities\Fund;

/**
 * Notify that the provider added a new product while being allowed to sell any of their products
 */
class IdentityRequesterProductAddedNotification extends BaseIdentityFundNotification
{
    protected static $key = 'notifications_identities.requester_product_added';
    protected static $visible = true;
}
