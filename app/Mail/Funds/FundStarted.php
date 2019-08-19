<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;

class FundStarted extends ImplementationMail
{
    private $fund_name;
    private $sponsor_name;

    public function __construct(
        string $email,
        string $fund_name,
        string $sponsor_name,
        ?string $identityId
    ) {
        parent::__construct($email, $identityId);
        $this->fund_name     = $fund_name;
        $this->sponsor_name  = $sponsor_name;
    }

    public function build(): ImplementationMail
    {
        return $this
            ->from(config('forus.mail.from.no-reply'), config('forus.mail.from.name'))
            ->to($this->email)
            ->subject(mail_trans('new_fund_created.title'))
            ->view('emails.funds.new_fund_created', [
                'fund_name'      => $this->fund_name,
                'sponsor_name'   => $this->sponsor_name
            ]);
    }
}
