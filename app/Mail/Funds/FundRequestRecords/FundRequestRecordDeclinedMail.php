<?php

namespace App\Mail\FundRequests;

use App\Mail\ImplementationMail;

/**
 * Class FundRequestCreatedMail
 * @package App\Mail\FundRequests
 */
class FundRequestRecordDeclinedMail extends ImplementationMail
{
    private $rejectionNote;
    private $fundName;
    private $link;

    public function __construct(
        string $fundName,
        string $rejectionNote,
        string $link,
        string $identityId = null
    ) {
        parent::__construct($identityId);
        $this->rejectionNote = $rejectionNote;
        $this->fundName = $fundName;
        $this->link = $link;
    }

    public function build(): ImplementationMail
    {
        return parent::build()
            ->subject(mail_trans('fund_request_record_declined.title', ['fund_name' => $this->fundName]))
            ->view('emails.funds.fund-request-records.fund_request-declined', [
                'fund_name' => $this->fundName,
                'webshop_link' => $this->link,
                'rejection_note' => $this->rejectionNote,
            ]);
    }
}
