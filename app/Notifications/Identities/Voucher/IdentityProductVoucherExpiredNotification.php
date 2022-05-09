<?php

namespace App\Notifications\Identities\Voucher;

/**
 * Product voucher expired
 */
class IdentityProductVoucherExpiredNotification extends BaseIdentityVoucherNotification
{
    protected static ?string $key = 'notifications_identities.product_voucher_expired';
}
