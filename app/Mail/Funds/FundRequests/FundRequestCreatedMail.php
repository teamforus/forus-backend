<?php

namespace App\Mail\FundRequests;

use App\Mail\ImplementationMail;

/**
 * Class FundRequestCreatedMail
 * @package App\Mail\FundRequests
 */
class FundRequestCreatedMail extends ImplementationMail
{
    private $fundName;
    private $link;

    public function __construct(
        string $fundName,
        string $link,
        string $identityId = null
    ) {
        parent::__construct($identityId);
        $this->fundName = $fundName;
        $this->link = $link;
    }

    public function build(): ImplementationMail
    {
        return parent::build()
            ->subject(mail_trans('fund_request_created.title', ['fund_name' => $this->fundName]))
            ->view('emails.funds.fund-requests.fund_request-created', [
                'fund_name' => $this->fundName,
                'webshop_link' => $this->link
            ]);
    }
}
