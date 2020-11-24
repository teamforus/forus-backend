<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;
use Illuminate\Mail\Mailable;

/**
 * Class PaymentSuccessMail
 * @package App\Mail\Vouchers
 */
class SubsidyPaymentSuccessMail extends ImplementationMail
{
    private $transData;

    public function __construct(
        array $data = [],
        ?EmailFrom $emailFrom = null
    ) {
        $this->setMailFrom($emailFrom);
        $this->transData = compact('data');
    }

    /**
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildBase()
            ->subject(mail_trans('subsidy_payment_success.title', $this->transData['data']))
            ->view('emails.vouchers.subsidy_payment_success', $this->transData);
    }
}
