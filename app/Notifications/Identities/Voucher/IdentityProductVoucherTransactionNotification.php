<?php

namespace App\Notifications\Identities\Voucher;

/**
 * New product voucher transaction created
 */
class IdentityProductVoucherTransactionNotification extends BaseIdentityVoucherNotification
{
    protected static ?string $key = 'notifications_identities.product_voucher_transaction';
    protected static ?string $pushKey = 'voucher.transaction';
}
