<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;

/**
 * Class PaymentSuccessMail
 * @package App\Mail\Vouchers
 */
class PaymentSuccessMail extends ImplementationMail
{
    private $fundName;
    private $currentBudget;

    public function __construct(
        string $fundName,
        string $currentBudget,
        ?EmailFrom $emailFrom
    ) {
        parent::__construct($emailFrom);

        $this->fundName = $fundName;
        $this->currentBudget = $currentBudget;
    }

    public function build(): ImplementationMail
    {
        return parent::build()
            ->subject(mail_trans('payment_success.title', [
                'fund_name' => $this->fundName
            ]))
            ->view('emails.vouchers.payment_success', [
                'fund_name' => $this->fundName,
                'current_budget' => $this->currentBudget
            ]);
    }
}
