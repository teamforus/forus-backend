<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;

/**
 * Class ProductBoughtMail
 * @package App\Mail\Vouchers
 */
class ProductBoughtMail extends ImplementationMail
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
        return parent::build()
            ->subject(mail_trans('product_bought.title', [
                'product_name' => $this->productName
            ]))
            ->view('emails.funds.product_bought', [
                'product_name' => $this->productName,
                'expiration_date' => $this->expirationDate
            ]);
    }
}
