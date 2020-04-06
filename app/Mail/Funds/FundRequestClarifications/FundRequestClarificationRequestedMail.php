<?php

namespace App\Mail\FundRequests;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;

/**
 * Class FundRequestCreatedMail
 * @package App\Mail\FundRequests
 */
class FundRequestClarificationRequestedMail extends ImplementationMail
{
    private $linkClarification;
    private $question;
    private $fundName;
    private $link;

    public function __construct(
        string $fundName,
        string $question,
        string $linkClarification,
        string $link,
        ?EmailFrom $emailFrom
    ) {
        parent::__construct($emailFrom);
        $this->fundName = $fundName;
        $this->question = $question;
        $this->linkClarification = $linkClarification;
        $this->link = $link;
    }

    public function build(): ImplementationMail
    {
        return parent::build()
            ->subject(mail_trans('fund_request_clarification_requested.title', ['fund_name' => $this->fundName]))
            ->view('emails.funds.fund-request-clarifications.fund_request-clarification-requested', [
                'fund_name' => $this->fundName,
                'question' => $this->question,
                'webshop_link' => $this->link,
                'webshop_link_clarification' => $this->linkClarification,
            ]);
    }
}
