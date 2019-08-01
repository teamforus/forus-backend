<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;

class FundCreated extends ImplementationMail
{
    private $fund_name;
    private $sponsor_dashboard_link;

    public function __construct(
        string $email,        
        string $fund_name,
        string $webshop_link
        ?string $identityId
    ) {
        parent::__construct($email, $identityId);
        $this->fund_name                = $fund_name;
        $this->webshop_link   = $webshop_link;
    }

    public function build(): Mailable
    {
        return $this
        ->from(config('forus.mail.from.no-reply'))
        ->to($this->email)
        ->subject(trans())
        ->view('emails.funds.new_fund_created', [
            'fund_name'      => $this->$fund_name,
            'webshop_link'   => $this->$webshop_link,
            'implementation' => $this->getImplementation()
        ]);
    }
}
