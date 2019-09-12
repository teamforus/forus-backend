<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;

/**
 * Class BalanceWarningMail
 * @package App\Mail\Funds
 */
class FundBalanceWarningMail extends ImplementationMail
{
    private $fundName;
    private $sponsorName;
    private $notificationAmount;
    private $link;

    public function __construct(
        string $fund_name,
        string $sponsor_name,
        string $notification_amount,
        string $link,
        ?string $identityId
    ) {
        parent::__construct($identityId);

        $this->fundName = $fund_name;
        $this->sponsorName = $sponsor_name;
        $this->notificationAmount = $notification_amount;
        $this->link = $link;
    }

    public function build(): ImplementationMail
    {
        return parent::build()
            ->subject(mail_trans('balance_warning.title'))
            ->view('emails.funds.balance_warning', [
                'fund_name' => $this->fundName,
                'sponsor_name' => $this->sponsorName,
                'notification_amount' => $this->notificationAmount,
                'link' => $this->link
            ]);
    }
}
