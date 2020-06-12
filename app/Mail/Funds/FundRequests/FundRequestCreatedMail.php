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
    private $link;

    public function __construct(
        string $fundName,
        string $link,
        ?EmailFrom $emailFrom
    ) {
        $this->setMailFrom($emailFrom);
        $this->fundName = $fundName;
        $this->link = $link;
    }

    public function build(): Mailable
    {
        return $this->buildBase()
            ->subject(mail_trans('fund_request_created.title', ['fund_name' => $this->fundName]))
            ->view('emails.funds.fund-requests.fund_request-created', [
                'fund_name' => $this->fundName,
                'webshop_link' => $this->link
            ]);
    }
}
