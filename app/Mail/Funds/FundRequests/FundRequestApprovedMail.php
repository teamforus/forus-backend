<?php

namespace App\Mail\Funds\FundRequests;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;
use Illuminate\Mail\Mailable;

class FundRequestApprovedMail extends ImplementationMail
{
    private $fundName;
    private $link;
    private $appLink;

    /**
     * Create a new message instance.
     *
     * FundRequestApprovedMail constructor.
     * @param string $fundName
     * @param string $link
     * @param string $appLink
     * @param EmailFrom|null $emailFrom
     */
    public function __construct(
        string $fundName,
        string $link,
        string $appLink,
        ?EmailFrom $emailFrom
    ) {
        $this->setMailFrom($emailFrom);
        $this->fundName = $fundName;
        $this->appLink = $appLink;
        $this->link = $link;
    }

    /**
     * @return string
     */
    protected function getSubject(): string
    {
        return mail_trans("fund_request_approved.title_$this->communicationType", [
            'fund_name' => $this->fundName
        ]);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): Mailable
    {
        return $this->buildBase()
            ->subject($this->getSubject())
            ->view('emails.funds.fund-requests.fund_request-approved', [
                'fund_name'     => $this->fundName,
                'app_link'      => $this->appLink,
                'webshop_link'  => $this->link,
            ]);
    }
}
