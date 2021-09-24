<?php

namespace App\Notifications\Identities\Voucher;

/**
 * The voucher will expire soon (product voucher)
 */
class IdentityVoucherExpireSoonProductNotification extends BaseIdentityVoucherNotification
{
    protected static $key = 'notifications_identities.voucher_expire_soon_product';

    protected static $visible = true;
    protected static $editable = true;
}
