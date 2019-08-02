<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;

class FundStatistics extends ImplementationMail
{
    private $fundName;
    private $sponsorName;
    private $sponsorAmount;
    private $providerAmount;
    private $requestAmount;
    private $totalAmount;

    public function __construct(
        string $email,
        string $fundName,
        string $sponsorName,
        string $sponsorAmount,
        string $providerAmount,
        string $requestAmount,
        string $totalAmount
    ) {
        parent::__construct($email, null);

        $this->fundName = $fundName;
        $this->sponsorName = $sponsorName;
        $this->sponsorAmount = $sponsorAmount;
        $this->providerAmount = $providerAmount;
        $this->requestAmount = $requestAmount;
        $this->totalAmount = $totalAmount;
    }

    public function build(): ImplementationMail
    {
        return $this
            ->from(config('forus.mail.from.no-reply'))
            ->to($this->email)
            ->subject(implementation_trans('fund_statistics.title', [
                    'sponsor_name' => $this->sponsorName,
                    'fund_name' => $this->fundName
                ]))
            ->view('emails.forus.fund_statistics', [
                'fund_name' => $this->fundName,
                'sponsor_name' => $this->sponsorName,
                'sponsor_amount' => $this->sponsorAmount,
                'provider_amount' => $this->providerAmount,
                'request_amount' => $this->requestAmount,
                'total_amount' => $this->totalAmount
            ]);
    }
}
