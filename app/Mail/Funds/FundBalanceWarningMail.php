<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;

/**
 * Class BalanceWarningMail
 * @package App\Mail\Funds
 */
class FundBalanceWarningMail extends ImplementationMail
{
    private $fundName;
    private $sponsorName;
    private $notificationAmount;
    private $budgetLeft;
    private $iban;
    private $topup_code;
    private $link;

    public function __construct(
        string $fund_name,
        string $sponsor_name,
        string $notification_amount,
        string $budget_left,
        string $link,
        string $iban,
        string $topup_code,
        ?EmailFrom $emailFrom
    ) {
        parent::__construct($emailFrom);

        $this->fundName = $fund_name;
        $this->sponsorName = $sponsor_name;
        $this->notificationAmount = $notification_amount;
        $this->budgetLeft = $budget_left;
        $this->iban = $iban;
        $this->topup_code = $topup_code;
        $this->link = $link;
    }

    public function build(): ImplementationMail
    {
        return parent::build()
            ->subject(mail_trans('balance_warning.title', ['fund_name' => $this->fundName]))
            ->view('emails.funds.balance_warning', [
                'fund_name' => $this->fundName,
                'sponsor_name' => $this->sponsorName,
                'notification_amount' => $this->notificationAmount,
                'budget_left' => $this->budgetLeft,
                'iban' => $this->iban,
                'topup_code' => $this->topup_code,
                'link' => $this->link
            ]);
    }
}
