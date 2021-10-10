<?php

namespace App\Notifications\Identities\Voucher;

/**
 * Send voucher to owner's email
 */
class IdentityVoucherSharedByEmailNotification extends BaseIdentityVoucherNotification
{
    protected static $key = 'notifications_identities.voucher_shared_by_email';
    protected static $scope = null;
}
