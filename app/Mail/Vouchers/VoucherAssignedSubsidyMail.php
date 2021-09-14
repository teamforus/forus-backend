<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

/**
 * Class AssignedVoucherMail
 * @package App\Mail\Vouchers
 */
class VoucherAssignedSubsidyMail extends ImplementationMail
{
    protected $notificationTemplateKey = 'notifications_identities.identity_voucher_assigned_subsidy';

    public function build(): Mailable
    {
        return $this->buildTemplatedNotification([
            'link_webshop' => $this->makeLink($this->mailData['link_webshop'], 'website'),
            'qr_token' => $this->makeQrCode($this->mailData['qr_token']),
        ]);
    }
}
