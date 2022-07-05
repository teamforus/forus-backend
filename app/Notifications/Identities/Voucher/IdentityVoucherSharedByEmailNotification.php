<?php

namespace App\Notifications\Identities\Voucher;

/**
 * Send voucher to owner's email
 */
class IdentityVoucherSharedByEmailNotification extends BaseIdentityVoucherNotification
{
    protected static ?string $key = 'notifications_identities.voucher_shared_by_email';
    protected static ?string $scope = null;
}
