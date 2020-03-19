<?php


namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;

/**
 * Class ProductSoldOutMail
 * @package App\Mail\Vouchers
 */
class ProductSoldOutMail extends ImplementationMail
{
    private $productName;
    private $link;

    public function __construct(
        string $productName,
        string $link,
        ?EmailFrom $emailFrom
    ){
        parent::__construct($emailFrom);

        $this->productName = $productName;
        $this->link = $link;
    }

    public function build(): ImplementationMail
    {
        return parent::build()
            ->subject(mail_trans('product_sold_out.title', [
                'product_name' => $this->productName
            ]))
            ->view('emails.funds.product_sold_out', [
                'product_name' => $this->productName,
                'link' => $this->link
            ]);
    }
}
