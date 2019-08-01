<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;

class ProviderApplied extends ImplementationMail
{

    private $provider_name;
    private $sponsor_name;
    private $fund_name;
    private $sponsor_dashboard_link;

    public function __construct(
        string $provider_name,
        string $sponsor_name,
        string $fund_name,
        string $sponsor_dashboard_link
    ) {
        $this->provider_name            = $provider_name;
        $this->sponsor_name             = $sponsor_name;
        $this->fund_name                = $fund_name;
        $this->sponsor_dashboard_link   = $sponsor_dashboard_link;
    }

    public function build(): Mailable
    {
        return $this
        ->from(config('forus.mail.from.no-reply'))
        ->to($this->email)
        ->subject(trans())
        ->view('emails.funds.provider_applied', [
            'provider_name'             => $provider_name,
            'sponsor_name'              => $sponsor_name,
            'fund_name'                 => $fund_name,
            'sponsor_dashboard_link'    => $sponsor_dashboard_link,
            'implementation' => config('forus.mails.implementations.' . $this->implementation)
        ]);
    }
}
