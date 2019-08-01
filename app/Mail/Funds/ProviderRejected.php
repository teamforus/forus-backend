<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;

class ProviderRejected extends ImplementationMail
{

    public function __construct(
        string $email,
        string $fund_name,
        string $provider_name,
        string $sponsor_name,
        ?string $identityId
    ) {
        parent::__construct($email, $identityId);

        $this->fund_name                = $fund_name;
        $this->provider_name            = $provider_name;
        $this->sponsor_name             = $sponsor_name;
    }
    public function build(): Mailable
    {
        return $this
        ->from(config('forus.mail.from.no-reply'))
        ->to($this->email)
        ->subject(trans())
        ->view('emails.funds.provider_rejected', [
            'fund_name'                 => $this->$fund_name,
            'provider_name'             => $this->$provider_name,
            'sponsor_name'              => $this->$sponsor_name,
            'implementation' => $this->getImplementation()
        ]);
    }
}
