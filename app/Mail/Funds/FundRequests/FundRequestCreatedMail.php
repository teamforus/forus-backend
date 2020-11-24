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

    public function build(): Mailable
    {
        return $this->buildBase()
            ->subject(mail_trans('fund_request_created.title', ['fund_name' => $this->fundName]))
            ->view('emails.funds.fund-requests.fund_request-created', [
                'fund_name'     => $this->fundName,
                'sponsor_name'  => $this->sponsorName,
                'webshop_link'  => $this->link
            ]);
    }
}
