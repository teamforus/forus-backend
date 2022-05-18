<?php

namespace App\Notifications\Identities\Voucher;

/**
 * Budget voucher was created
 */
class IdentityVoucherAddedBudgetNotification extends BaseIdentityVoucherNotification
{
    protected static ?string $key = 'notifications_identities.voucher_added_budget';
}
