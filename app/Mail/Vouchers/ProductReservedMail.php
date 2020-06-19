<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;
use Illuminate\Mail\Mailable;

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
        ?EmailFrom $emailFrom
    ) {
        $this->setMailFrom($emailFrom);

        $this->productName = $productName;
        $this->expirationDate = $expirationDate;
    }

    public function build(): Mailable
    {
        return $this->buildBase()
            ->subject(mail_trans('product_bought.title', [
                'product_name' => $this->productName
            ]))
            ->view('emails.funds.product_bought', [
                'product_name' => $this->productName,
                'expiration_date' => $this->expirationDate
            ]);
    }
}
