<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;
use Illuminate\Mail\Mailable;

/**
 * Class PaymentSuccessMail
 * @package App\Mail\Vouchers
 */
class PaymentSuccessMail extends ImplementationMail
{
    private $transData;

    public function __construct(
        array $data = [],
        ?EmailFrom $emailFrom = null
    ) {
        $this->setMailFrom($emailFrom);

        $this->transData['data'] = $data;
    }

    public function build(): Mailable
    {
        return $this->buildBase()
            ->subject(mail_trans('payment_success.title', $this->transData['data']))
            ->view('emails.vouchers.payment_success', $this->transData['data']);
    }
}
