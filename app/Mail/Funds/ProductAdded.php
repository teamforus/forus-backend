<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;

class ProductAdded extends ImplementationMail
{
    private $sponsorName;
    private $fundName;

    public function __construct(
        string $email,
        string $sponsorName,
        string $fundName,
        ?string $identityId
    ) {
        parent::__construct($email, $identityId);

        $this->sponsorName = $sponsorName;
        $this->fundName = $fundName;
    }

    public function build(): ImplementationMail
    {
        return $this
            ->from(config('forus.mail.from.no-reply'), config('forus.mail.from.name'))
            ->to($this->email)
            ->subject(mail_trans('product_added.title'))
            ->view('emails.funds.product_added', [
                'fund_name' => $this->fundName,
                'sponsor_name' => $this->sponsorName
            ]);
    }
}
