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

    /**
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildNotificationTemplatedMail();
    }

    /**
     * @param array $data
     * @return array
     */
    protected function getMailExtraData(array $data): array
    {
        return [
            'qr_token' => $this->makeQrCode($data['qr_token']),
            'webshop_link' => $this->makeLink($data['webshop_link'], 'website'),
            'webshop_button' => $this->makeLink($data['webshop_link'], 'website'),
        ];
    }
}
