<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;

class PaymentSuccesss extends ImplementationMail
{
    private $fundName;
    private $currentBudget;

    public function __construct(
        string $email,
        string $fundName,
        string $currentBudget,
        ?string $identityId
    ) {
        parent::__construct($email, $identityId);

        $this->fundName = $fundName;
        $this->currentBudget = $currentBudget;
    }

    public function build(): ImplementationMail
    {
        return $this
            ->from(config('forus.mail.from.no-reply'))
            ->to($this->email)
            ->subject('Er is een bedrag van uw ' . $this->fundName . '-voucher afgeschreven')
            ->view('emails.vouchers.payment_success', [
                'fund_name' => $this->fundName,
                'current_budget' => $this->currentBudget
            ]);
    }
}
