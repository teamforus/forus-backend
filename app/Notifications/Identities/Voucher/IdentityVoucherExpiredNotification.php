<?php

namespace App\Notifications\Identities\Voucher;

class IdentityVoucherExpiredNotification extends BaseIdentityVoucherNotification
{
    protected static $key = 'notifications_identities.budget_voucher_expired';
    protected static $visible = true;
}
