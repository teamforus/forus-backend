<?php

namespace App\Mail\Funds\FundRequests;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;
use Illuminate\Mail\Mailable;

/**
 * Class FundRequestDeniedMail
 * @package App\Mail\Funds\FundRequests
 */
class FundRequestDeniedMail extends ImplementationMail
{
    private $fundName;
    private $clarificationMsg;
    private $sponsorName;
    private $sponsorPhone;
    private $sponsorEmail;

    /**
     * Create a new message instance.
     *
     * FundRequestApprovedMail constructor.
     * @param string $fundName
     * @param string $clarificationMsg
     * @param string $sponsorName
     * @param string $sponsorPhone
     * @param string $sponsorEmail
     * @param EmailFrom|null $emailFrom
     */
    public function __construct(
        string $fundName,
        string $clarificationMsg,
        string $sponsorName,
        string $sponsorPhone,
        string $sponsorEmail,
        ?EmailFrom $emailFrom
    ) {
        $this->setMailFrom($emailFrom);
        $this->fundName = $fundName;
        $this->clarificationMsg = $clarificationMsg;
        $this->sponsorName = $sponsorName;
        $this->sponsorPhone = $sponsorPhone;
        $this->sponsorEmail = $sponsorEmail;
    }

    /**
     * @return string
     */
    protected function getSubject(): string
    {
        return mail_trans("fund_request_denied.title_$this->communicationType", [
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
            ->view('emails.funds.fund-requests.fund_request-denied', [
                'fund_name'         => $this->fundName,
                'sponsor_name'      => $this->sponsorName,
                'sponsor_phone'     => $this->sponsorPhone,
                'sponsor_email'     => $this->sponsorEmail,
                'clarification_msg' => $this->clarificationMsg,
            ]);
    }
}
