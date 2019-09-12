<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;

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
        string $email,
        string $fundName,
        string $providerName,
        string $sponsorName,
        string $link,
        ?string $identityId
    ) {
        parent::__construct($email, $identityId);

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
                'provider_dashboard_link'   => $this->providerName
            ]);
    }

}
