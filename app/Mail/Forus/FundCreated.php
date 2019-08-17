<?php

namespace App\Mail\Funds\Forus;

use App\Mail\ImplementationMail;

class FundCreated extends ImplementationMail
{
    private $fundName;
    private $organizationName;

    public function __construct(
        string $email,
        string $fundName,
        string $organizationName
    ) {
        parent::__construct($email, null);

        $this->fundName = $fundName;
        $this->organizationName = $organizationName;
    }

    public function build(): ImplementationMail
    {
        return $this
            ->from(config('forus.mail.from.no-reply'), config('forus.mail.from.name'))
            ->to($this->email)
            ->subject(implementation_trans('fund_created.title', ['fund_name' => $this->fundName]))
            ->view('emails.forus.new_fund_created', [
                'fund_name' => $this->fundName,
                'organization_name' => $this->organizationName
            ]);
    }
}
