<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

/**
 * Class PaymentSuccessMail
 * @package App\Mail\Vouchers
 */
class PaymentSuccessSubsidyMail extends ImplementationMail
{
    protected $notificationTemplateKey = 'notifications_identities.voucher_subsidy_transaction';

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
            'webshop_link' => $this->makeLink($data['webshop_link'], $data['webshop_link']),
            'webshop_button' => $this->makeButton($data['webshop_link'], 'Ga naar webshop'),
        ];
    }
}
