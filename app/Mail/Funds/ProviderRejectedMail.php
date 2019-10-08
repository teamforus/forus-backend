<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;

/**
 * Class ProviderRejectedMail
 * @package App\Mail\Funds
 */
class ProviderRejectedMail extends ImplementationMail
{
    private $fundName;
    private $providerName;
    private $sponsorName;
    private $phoneNumber;

    public function __construct(
        string $fund_name,
        string $provider_name,
        string $sponsor_name,
        string $phone_number,
        string $identityId = null
    ) {
        parent::__construct($identityId);

        $this->fundName                = $fund_name;
        $this->providerName            = $provider_name;
        $this->sponsorName             = $sponsor_name;
        $this->phoneNumber             = $phone_number;
    }
    public function build(): ImplementationMail
    {
        return parent::build()
            ->subject(mail_trans('provider_rejected.title'))
            ->view('emails.funds.provider_rejected', [
                'fund_name'                 => $this->fundName,
                'provider_name'             => $this->providerName,
                'sponsor_name'              => $this->sponsorName,
                'phone_number'              => $this->phoneNumber
            ]);
    }
}
