<?php

namespace App\Mail\Funds\Forus;

use App\Mail\ImplementationMail;

/**
 * Class FundCreatedMail
 * @package App\Mail\Funds\Forus
 */
class ForusFundCreated extends ImplementationMail
{
    private $fundName;
    private $organizationName;

    public function __construct(
        string $fundName,
        string $organizationName
    ) {
        parent::__construct(null);

        $this->fundName = $fundName;
        $this->organizationName = $organizationName;
    }

    public function build(): ImplementationMail
    {
        return parent::build()
            ->subject(mail_trans('forus/fund_created.title', [
                'fund_name' => $this->fundName
            ]))
            ->view('emails.forus.new_fund_created', [
                'fund_name' => $this->fundName,
                'organization_name' => $this->organizationName
            ]);
    }
}
