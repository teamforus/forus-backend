<?php


namespace App\Mail\Vouchers;


use App\Mail\ImplementationMail;

class ProductSoldOut extends ImplementationMail
{
    private $productName;
    private $link;

    public function __construct(
        string $email,
        string $productName,
        string $link,
        ?string $identityId
    ){
        parent::__construct($email, $identityId);

        $this->productName = $productName;
        $this->link = $link;
    }

    public function build(): ImplementationMail
    {
        return $this
            ->from(config('forus.mail.from.no-reply'), config('forus.mail.from.name'))
            ->to($this->email)
            ->subject(mail_trans('product_sold_out.title', ['product_name' => $this->productName]))
            ->view('emails.funds.product_sold_out', [
                'product_name' => $this->productName,
                'link' => $this->link
            ]);
    }
}
