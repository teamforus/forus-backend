<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;
use Illuminate\Mail\Mailable;

/**
 * Class BalanceWarningMail
 * @package App\Mail\Funds
 */
class FundBalanceWarningMail extends ImplementationMail
{
    private $fundName;
    private $sponsorName;
    private $notificationAmount;
    private $transactionCosts;
    private $budgetLeft;
    private $link;

    public function __construct(
        string $fund_name,
        string $sponsor_name,
        string $notification_amount,
        string $transaction_costs,
        string $budget_left,
        string $link,
        ?EmailFrom $emailFrom
    ) {
        $this->setMailFrom($emailFrom);

        $this->fundName = $fund_name;
        $this->sponsorName = $sponsor_name;
        $this->notificationAmount = $notification_amount;
        $this->transactionCosts = $transaction_costs;
        $this->budgetLeft = $budget_left;
        $this->link = $link;
    }

    public function build(): Mailable
    {
        return $this->buildBase()
            ->subject(mail_trans('balance_warning.title', ['fund_name' => $this->fundName]))
            ->view('emails.funds.balance_warning', [
                'fund_name' => $this->fundName,
                'sponsor_name' => $this->sponsorName,
                'notification_amount' => $this->notificationAmount,
                'transaction_costs'   => $this->transactionCosts,
                'budget_left' => $this->budgetLeft,
                'link' => $this->link
            ]);
    }
}
