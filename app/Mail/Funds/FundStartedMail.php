<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;

/**
 * Class FundStartedMail
 * @package App\Mail\Funds
 */
class FundStartedMail extends ImplementationMail
{
    private $fund_name;
    private $sponsor_name;

    public function __construct(
        string $fund_name,
        string $sponsor_name,
        string $identityId = null
    ) {
        parent::__construct($identityId);

        $this->fund_name     = $fund_name;
        $this->sponsor_name  = $sponsor_name;
    }

    public function build(): ImplementationMail
    {
        return parent::build()
            ->subject(mail_trans('fund_started.title', [
                'fund_name' => $this->fund_name
            ]))
            ->view('emails.funds.fund_started', [
                'fund_name'      => $this->fund_name,
                'sponsor_name'   => $this->sponsor_name
            ]);
    }
}
