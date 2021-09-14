<?php

namespace App\Notifications\Identities\Voucher;

class IdentityProductVoucherExpiredNotification extends BaseIdentityVoucherNotification
{
    protected static $key = 'notifications_identities.product_voucher_expired';
    protected static $visible = true;
}
