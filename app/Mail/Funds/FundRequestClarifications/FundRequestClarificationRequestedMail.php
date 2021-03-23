<?php

namespace App\Mail\Funds\FundRequestClarifications;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;
use Illuminate\Mail\Mailable;

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
        $this->setMailFrom($emailFrom);
        $this->fundName = $fundName;
        $this->question = $question;
        $this->linkClarification = $linkClarification;
        $this->link = $link;
    }

    /**
     * @return string
     */
    protected function getSubject(): string
    {
        return mail_trans("fund_request_clarification_requested.title_$this->communicationType", [
            'fund_name' => $this->fundName
        ]);
    }

    /**
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildBase()->subject($this->getSubject())
            ->view('emails.funds.fund-request-clarifications.fund_request-clarification-requested', [
                'fund_name' => $this->fundName,
                'question' => $this->question,
                'webshop_link' => $this->link,
                'webshop_link_clarification' => $this->linkClarification,
            ]);
    }
}
