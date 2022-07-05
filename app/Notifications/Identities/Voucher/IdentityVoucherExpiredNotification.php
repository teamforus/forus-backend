<?php

namespace App\Notifications\Identities\Voucher;

/**
 * The voucher expired (budget/subsidy)
 */
class IdentityVoucherExpiredNotification extends BaseIdentityVoucherNotification
{
    protected static ?string $key = 'notifications_identities.budget_voucher_expired';
}
