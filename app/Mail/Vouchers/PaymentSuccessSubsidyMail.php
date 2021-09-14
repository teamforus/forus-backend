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

    public function build(): Mailable
    {
        $link = $this->mailData['webshop_link'];

        return $this->buildTemplatedNotification([
            'webshop_link' => $this->makeLink($link, $link),
            'webshop_button' => $this->makeButton($link, 'Ga naar webshop'),
        ]);
    }
}
