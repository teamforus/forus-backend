<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;

class NewFundApplicable extends ImplementationMail
{
    private $fundName;
    private $link;

    public function __construct(
        string $email,
        string $fundName,
        string $link,
        ?string $identityId)
    {
        parent::__construct($email, $identityId);
        $this->fundName = $fundName;
        $this->link = $link;
    }

    public function build(): ImplementationMail
    {
        return $this
            ->from(config('forus.mail.from.no-reply'))
            ->to($this->email)
            ->subject(trans('mails.funds.new_fund_created.title'))
            ->view('emails.funds.new_fund_created', [
                'fund_name' => $this->fundName,
                'webshop_link' => $this->link,
                'implementation' => $this->getImplementation()
            ]);
    }
}
