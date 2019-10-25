<?php

namespace App\Mail\FundRequests;

use App\Mail\ImplementationMail;

/**
 * Class FundRequestCreatedMail
 * @package App\Mail\FundRequests
 */
class FundRequestResolvedMail extends ImplementationMail
{
    private $requestStatus;
    private $fundName;
    private $link;

    public function __construct(
        string $requestStatus,
        string $fundName,
        string $link,
        string $identityId = null
    ) {
        parent::__construct($identityId);
        $this->requestStatus = $requestStatus;
        $this->fundName = $fundName;
        $this->link = $link;
    }

    public function build(): ImplementationMail
    {
        return parent::build()
            ->subject(mail_trans('fund_request_resolved.title', ['fund_name' => $this->fundName]))
            ->view('emails.funds.fund-requests.fund_request-resolved', [
                'fund_name' => $this->fundName,
                'webshop_link' => $this->link,
                'request_status' => $this->requestStatus,
            ]);
    }
}
