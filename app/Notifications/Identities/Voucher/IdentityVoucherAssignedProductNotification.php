<?php

namespace App\Notifications\Identities\Voucher;

use App\Mail\Vouchers\VoucherAssignedProductMail;
use App\Models\Identity;
use App\Models\Voucher;

/**
 * Product voucher was assigned to identity
 */
class IdentityVoucherAssignedProductNotification extends BaseIdentityVoucherNotification
{
    protected static ?string $key = 'notifications_identities.identity_voucher_assigned_product';
    protected static ?string $pushKey = 'voucher.assigned';
    protected static ?string $scope = null;

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var Voucher $voucher */
        $voucher = $this->eventLog->loggable;

        if ($voucher->expired) {
            return;
        }

        $mailable = new VoucherAssignedProductMail(array_merge($this->eventLog->data, [
            'qr_token' => $voucher->token_without_confirmation->address,
            'webshop_link' => $voucher->fund->urlWebshop(),
        ]), $voucher->fund->getEmailFrom());

        $this->sendMailNotification($identity->email, $mailable);
    }
}
