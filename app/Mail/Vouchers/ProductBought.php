<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;

class ProductBought extends ImplementationMail
{
    private $productName;
    private $expirationDate;

    public function __construct(
        string $email,
        string $productName,
        string $expirationDate,
        ?string $identityId
    ) {
        parent::__construct($email, $identityId);

        $this->productName = $productName;
        $this->expirationDate = $expirationDate;
    }

    public function build(): ImplementationMail
    {
        return $this
            ->from(config('forus.mail.from.no-reply'))
            ->to($this->email)
            ->subject('Uw aanbod' . $this->productName .' is gereserveerd!')
            ->view('emails.funds.product_bought', [
                'product_name' => $this->productName,
                'expiration_date' => $this->expirationDate,
                'implementation' => $this->getImplementation()
            ]);
    }
}
