<?php

namespace App\Notifications\Identities\Voucher;

/**
 * Product voucher was created
 */
class IdentityProductVoucherAddedNotification extends BaseIdentityVoucherNotification
{
    protected static $key = 'notifications_identities.product_voucher_added';
    protected static $visible = true;
}
