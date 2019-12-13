<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;

/**
 * Class ProviderAppliedMail
 * @package App\Mail\Funds
 */
class ProviderInvitedMail extends ImplementationMail
{

    private $provider_name;
    private $sponsor_name;
    private $sponsor_phone;
    private $sponsor_email;
    private $fund_name;
    private $fund_start_date;
    private $fund_end_date;
    private $from_fund_name;
    private $invitation_link;

    public function __construct(
        string $provider_name,
        string $sponsor_name,
        ?string $sponsor_phone,
        ?string $sponsor_email,
        string $fund_name,
        string $fund_start_date,
        string $fund_end_date,
        string $from_fund_name,
        string $invitation_link,
        string $identityId = null
    ) {
        parent::__construct($identityId);
        $this->provider_name            = $provider_name;
        $this->sponsor_name             = $sponsor_name;
        $this->sponsor_phone            = $sponsor_phone;
        $this->sponsor_email            = $sponsor_email;
        $this->fund_name                = $fund_name;
        $this->fund_start_date          = $fund_start_date;
        $this->fund_end_date            = $fund_end_date;
        $this->from_fund_name           = $from_fund_name;
        $this->invitation_link          = $invitation_link;
    }

    public function build(): ImplementationMail
    {
        return parent::build()
            ->subject(mail_trans('provider_invitation.title', [
                'sponsor_name' => $this->sponsor_name,
                'fund_name' => $this->fund_name
            ]))
            ->view('emails.funds.provider_invitation', [
                'provider_name'             => $this->provider_name,
                'sponsor_name'              => $this->sponsor_name,
                'sponsor_phone'             => $this->sponsor_phone,
                'sponsor_email'             => $this->sponsor_email,
                'fund_name'                 => $this->fund_name,
                'fund_start_date'           => $this->fund_start_date,
                'fund_end_date'             => $this->fund_end_date,
                'from_fund_name'            => $this->from_fund_name,
                'invitation_link'           => $this->invitation_link
            ]);
    }
}
