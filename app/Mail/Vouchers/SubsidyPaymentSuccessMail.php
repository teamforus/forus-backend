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
    /**
     * SubsidyPaymentSuccessMail constructor.
     * @param array $data
     * @param EmailFrom|null $emailFrom
     */
    public function __construct(
        array $data = [],
        ?EmailFrom $emailFrom = null
    ) {
        $this->setMailFrom($emailFrom);
        $this->mailData = $this->escapeData($data);
    }

    /**
     * @return string
     */
    protected function getSubject(): string
    {
        return mail_trans("subsidy_payment_success.title_$this->communicationType", $this->mailData);
    }

    /**
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildBase()
            ->subject($this->getSubject())
            ->view('emails.vouchers.subsidy_payment_success', [
                'data' => $this->mailData,
            ]);
    }
}
