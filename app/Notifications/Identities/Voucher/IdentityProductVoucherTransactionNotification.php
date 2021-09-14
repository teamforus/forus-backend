<?php

namespace App\Notifications\Identities\Voucher;

class IdentityProductVoucherTransactionNotification extends BaseIdentityVoucherNotification
{
    protected static $key = 'notifications_identities.product_voucher_transaction';
    protected static $pushKey = 'voucher.transaction';
    protected static $sendPush = true;

    protected static $visible = true;
}
