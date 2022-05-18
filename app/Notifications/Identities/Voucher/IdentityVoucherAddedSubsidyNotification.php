<?php

namespace App\Notifications\Identities\Voucher;

/**
 * Subsidy voucher was created
 */
class IdentityVoucherAddedSubsidyNotification extends BaseIdentityVoucherNotification
{
    protected static ?string $key = 'notifications_identities.voucher_added_subsidy';
}
