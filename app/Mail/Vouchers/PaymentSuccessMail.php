<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;
use App\Models\Fund;
use App\Services\Forus\Notification\EmailFrom;
use Illuminate\Mail\Mailable;

/**
 * Class PaymentSuccessMail
 * @package App\Mail\Vouchers
 */
class PaymentSuccessMail extends ImplementationMail
{
    /**
     * PaymentSuccessMail constructor.
     * @param array $data
     * @param EmailFrom|null $emailFrom
     */
    public function __construct(array $data = [], ?EmailFrom $emailFrom = null)
    {
        $this->setMailFrom($emailFrom);
        $this->mailData = $this->escapeData($data);
    }

    /**
     * @return string
     */
    protected function getSubject(): string
    {
        if ($this->mailData['fund_type'] === Fund::TYPE_SUBSIDIES) {
            return mail_trans("subsidy_payment_success.title_$this->communicationType", $this->mailData);
        }

        return mail_trans("payment_success.title_$this->communicationType", $this->mailData);
    }

    /**
     * @return Mailable
     */
    public function build(): Mailable
    {
        $data = $this->mailData;
        $mail = $this->buildBase()->subject($this->getSubject());

        if ($this->mailData['fund_type'] === Fund::TYPE_SUBSIDIES) {
            return $mail->view('emails.vouchers.subsidy_payment_success', compact('data'));
        }

        return $mail->view('emails.vouchers.payment_success', compact('data'));
    }
}
