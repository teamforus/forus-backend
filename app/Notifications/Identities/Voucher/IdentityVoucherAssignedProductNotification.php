<?php

namespace App\Notifications\Identities\Voucher;

use App\Mail\Vouchers\VoucherAssignedProductMail;
use App\Services\Forus\Identity\Models\Identity;
use App\Models\Voucher;

/**
 * Class IdentityVoucherAssignedBudgetNotification
 * @package App\Notifications\Identities\Voucher
 */
class IdentityVoucherAssignedProductNotification extends BaseIdentityVoucherNotification
{
    protected static $key = 'notifications_identities.identity_voucher_assigned_product';
    protected static $pushKey = 'voucher.assigned';
    protected static $sendMail = true;
    protected static $sendPush = true;
    protected static $scope = null;

    protected static $visible = true;
    protected static $editable = true;

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var Voucher $voucher */
        $voucher = $this->eventLog->loggable;

        $mailable = new VoucherAssignedProductMail(array_merge($this->eventLog->data, [
            'qr_token' => $voucher->token_without_confirmation->address,
            'link_webshop' => $voucher->fund->urlWebshop(),
        ]), $voucher->fund->getEmailFrom());

        $this->sendMailNotification($identity->email, $mailable);
    }
}
