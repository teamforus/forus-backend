<?php

namespace App\Notifications\Identities\Voucher;

class IdentityVoucherAddedBudgetNotification extends BaseIdentityVoucherNotification
{
    protected static $key = 'notifications_identities.voucher_added_budget';
    protected static $visible = true;
}
