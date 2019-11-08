<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;

/**
 * Class ProductAddedMail
 * @package App\Mail\Funds
 */
class ProductAddedMail extends ImplementationMail
{
    private $sponsorName;
    private $fundName;
    private $webshopLink;

    public function __construct(
        string $sponsorName,
        string $fundName,
        string $webshopLink,
        string $identityId = null
    ) {
        parent::__construct($identityId);

        $this->sponsorName = $sponsorName;
        $this->fundName = $fundName;
        $this->webshopLink = $webshopLink;
    }

    public function build(): ImplementationMail
    {
        return parent::build()
            ->subject(mail_trans('product_added.title'))
            ->view('emails.funds.product_added', [
                'fund_name' => $this->fundName,
                'sponsor_name' => $this->sponsorName,
                'webshop_link' => $this->webshopLink
            ]);
    }
}
