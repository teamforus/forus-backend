<?php

namespace App\Mail\Funds\FundRequests;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;
use Illuminate\Mail\Mailable;

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
        ?EmailFrom $emailFrom
    ) {
        $this->setMailFrom($emailFrom);
        $this->requestStatus = $requestStatus;
        $this->fundName = $fundName;
        $this->link = $link;
    }

    public function build(): Mailable
    {
        $this->communicationType =  $this->emailFrom->isInformalCommunication() ? 'informal' : 'formal';

        return $this->buildBase()
            ->subject(mail_trans('fund_request_resolved.title_' . $this->communicationType, ['fund_name' => $this->fundName]))
            ->view('emails.funds.fund-requests.fund_request-resolved', [
                'fund_name' => $this->fundName,
                'webshop_link' => $this->link,
                'request_status' => $this->requestStatus,
            ]);
    }
}
