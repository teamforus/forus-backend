<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;

class ProviderRejected extends ImplementationMail
{
    private $fundName;
    private $providerName;
    private $sponsorName;

    public function __construct(
        string $email,
        string $fund_name,
        string $provider_name,
        string $sponsor_name,
        ?string $identityId
    ) {
        parent::__construct($email, $identityId);

        $this->fundName                = $fund_name;
        $this->providerName            = $provider_name;
        $this->sponsorName             = $sponsor_name;
    }
    public function build(): ImplementationMail
    {
        return $this
        ->from(config('forus.mail.from.no-reply'))
        ->to($this->email)
        ->subject(trans('mails.funds.provider_rejected.title'))
        ->view('emails.funds.provider_rejected', [
            'fund_name'                 => $this->fundName,
            'provider_name'             => $this->providerName,
            'sponsor_name'              => $this->sponsorName
        ]);
    }
}
