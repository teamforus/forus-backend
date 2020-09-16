<?php

namespace App\Mail\Products;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;
use Illuminate\Mail\Mailable;

class ProductActionsRemovedMail extends ImplementationMail
{
    private $productName;
    private $sponsorDashboardLink;
    private $providerName;
    private $sponsorName;

    /**
     * Create a new message instance.
     *
     * ProductActionsRemovedMail constructor.
     * @param string $productName
     * @param string $providerName
     * @param string $sponsorName
     * @param string $sponsorDashboardLink
     * @param EmailFrom|null $emailFrom
     */
    public function __construct(
        string $productName,
        string $providerName,
        string $sponsorName,
        string $sponsorDashboardLink,
        ?EmailFrom $emailFrom
    ) {
        $this->productName  = $productName;
        $this->providerName = $providerName;
        $this->sponsorName  = $sponsorName;
        $this->sponsorDashboardLink = $sponsorDashboardLink;

        $this->setMailFrom($emailFrom);
    }

    /**
     * Build the message.
     *
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildBase()
            ->subject(mail_trans('product_actions_removed.title', [
                'provider_name'  => $this->providerName,
                'product_name'   => $this->productName
            ]))
            ->view('emails.products.product_actions_removed', [
                'provider_name'  => $this->providerName,
                'sponsor_name'   => $this->sponsorName,
                'product_name'   => $this->productName,
                'sponsor_dashboard_link' => $this->sponsorDashboardLink
            ]);
    }
}
