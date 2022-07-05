<?php

namespace App\Notifications\Identities\Voucher;

/**
 * Product voucher was created
 */
class IdentityProductVoucherAddedNotification extends BaseIdentityVoucherNotification
{
    protected static ?string $key = 'notifications_identities.product_voucher_added';
}
