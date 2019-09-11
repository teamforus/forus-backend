<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;

class BalanceWarning extends ImplementationMail
{
    private $fundName;
    private $sponsorName;
    private $notificationAmount;
    private $budgetLeft;
    private $link;

    public function __construct(
        string $email,
        string $fund_name,
        string $sponsor_name,
        string $notification_amount,
        string $budget_left,
        string $link,
        ?string $identityId
    ) {
        parent::__construct($email, $identityId);

        $this->fundName = $fund_name;
        $this->sponsorName = $sponsor_name;
        $this->notificationAmount = $notification_amount;
        $this->$budgetLeft = $budget_left;
        $this->link = $link;
    }

    public function build(): ImplementationMail
    {
        return $this
            ->from(config('forus.mail.from.no-reply'), config('forus.mail.from.name'))
            ->to($this->email)
            ->subject(mail_trans('balance_warning.title'), ['fund_name' => $this->fundName])
            ->view('emails.funds.balance_warning', [
                'fund_name' => $this->fundName,
                'sponsor_name' => $this->sponsorName,
                'notification_amount' => $this->notificationAmount,
                'budget_left' => $this->$budgetLeft,
                'link' => $this->link
            ]);
    }
}
