<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;

/**
 * Class ProviderApprovedMail
 * @package App\Mail\Funds
 */
class ProviderApprovedMail extends ImplementationMail
{
    private $fundName;
    private $providerName;
    private $sponsorName;
    private $link;

    public function __construct(
        string $fundName,
        string $providerName,
        string $sponsorName,
        string $link,
        ?EmailFrom $emailFrom
    ) {
        parent::__construct($emailFrom);

        $this->fundName = $fundName;
        $this->providerName = $providerName;
        $this->sponsorName = $sponsorName;
        $this->link = $link;
    }

    public function build(): ImplementationMail
    {
        return parent::build()
            ->subject(mail_trans('provider_approved.title'))
            ->view('emails.funds.provider_approved', [
                'fund_name'                 => $this->fundName,
                'provider_name'             => $this->providerName,
                'sponsor_name'              => $this->sponsorName,
                'provider_dashboard_link'    => $this->link
            ]);
    }

}
