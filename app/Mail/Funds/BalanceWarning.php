<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;

class BalanceWarning extends ImplementationMail
{
    private $fundName;
    private $sponsorName;
    private $notificationAmount;
    private $link;

    public function __construct(
        string $email,
        string $fund_name,
        string $sponsor_name,
        string $notification_amount,
        string $link,
        ?string $identityId
    ) {
        parent::__construct($email, $identityId);

        $this->fundName = $fund_name;
        $this->sponsorName = $sponsor_name;
        $this->notificationAmount = $notification_amount;
        $this->link = $link;
    }

    public function build(): ImplementationMail
    {
        return $this
            ->from(config('forus.mail.from.no-reply'))
            ->to($this->email)
            ->subject('Uw fonds heeft uw ingestelde grens bereikt')
            ->view('emails.funds.balance_warning', [
                'fund_name' => $this->fundName,
                'sponsor_name' => $this->sponsorName,
                'notification_amount' => $this->notificationAmount,
                'link' => $this->link
            ]);
    }
}
