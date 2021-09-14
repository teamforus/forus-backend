<?php

namespace App\Notifications\Identities\Voucher;

class IdentityVoucherSharedByEmailNotification extends BaseIdentityVoucherNotification
{
    protected static $key = 'notifications_identities.voucher_shared_by_email';
    protected static $scope = null;
    protected static $visible = true;
}
