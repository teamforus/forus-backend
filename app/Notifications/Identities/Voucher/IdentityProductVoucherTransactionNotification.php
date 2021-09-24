<?php

namespace App\Notifications\Identities\Voucher;

/**
 * New product voucher transaction created
 */
class IdentityProductVoucherTransactionNotification extends BaseIdentityVoucherNotification
{
    protected static $key = 'notifications_identities.product_voucher_transaction';
    protected static $pushKey = 'voucher.transaction';

    protected static $visible = true;
}
