<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;

/**
 * Class ProductReservedMail
 * @package App\Mail\Vouchers
 */
class ProductReservedMail extends ImplementationMail
{
    private $productName;
    private $expirationDate;

    public function __construct(
        string $productName,
        string $expirationDate,
        string $identityId = null
    ) {
        parent::__construct($identityId);

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
