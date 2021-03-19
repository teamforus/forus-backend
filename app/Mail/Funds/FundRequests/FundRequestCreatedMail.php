<?php

namespace App\Mail\Funds\FundRequests;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;
use Illuminate\Mail\Mailable;

/**
 * Class FundRequestCreatedMail
 * @package App\Mail\FundRequests
 */
class FundRequestCreatedMail extends ImplementationMail
{
    private $fundName;
    private $sponsorName;
    private $link;

    /**
     * FundRequestCreatedMail constructor.
     * @param string $fundName
     * @param string $sponsorName
     * @param string $link
     * @param EmailFrom|null $emailFrom
     */
    public function __construct(
        string $fundName,
        string $sponsorName,
        string $link,
        ?EmailFrom $emailFrom
    ) {
        $this->setMailFrom($emailFrom);
        $this->fundName = $fundName;
        $this->sponsorName = $sponsorName;
        $this->link = $link;
    }

    /**
     * @return string
     */
    protected function getSubject(): string
    {
        return mail_trans( "fund_request_created.title_$this->communicationType", [
            'fund_name' => $this->fundName
        ]);
    }

    /**
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildBase()
            ->subject($this->getSubject())
            ->view('emails.funds.fund-requests.fund_request-created', [
                'fund_name'     => $this->fundName,
                'sponsor_name'  => $this->sponsorName,
                'webshop_link'  => $this->link
            ]);
    }
}
