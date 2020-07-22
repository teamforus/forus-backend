<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;
use Illuminate\Mail\Mailable;

/**
 * Class ProductReservedUserMail
 * @package App\Mail\Vouchers
 */
class ProductReservedUserMail extends ImplementationMail
{
    private $productName;
    private $productPrice;
    private $provider_organization_name;
    private $provider_phone;
    private $provider_email;
    private $qr_token;
    private $expire_at_minus_1_day;

    public function __construct(
        string $productName,
        string $productPrice,
        string $providerPhone,
        string $providerEmail,
        string $qrToken,
        string $providerOrganizationName,
        string $expireAtMinus1Day,
        ?EmailFrom $emailFrom
    ) {
        $this->setMailFrom($emailFrom);

        $this->productName = $productName;
        $this->productPrice = $productPrice;
        $this->provider_organization_name = $providerOrganizationName;
        $this->provider_phone = $providerPhone;
        $this->provider_email = $providerEmail;
        $this->qr_token = $qrToken;
        $this->expire_at_minus_1_day = $expireAtMinus1Day;
    }

    public function build(): Mailable
    {
        return $this->buildBase()
            ->subject(mail_trans('product_reserved.title', [
                'product_name' => $this->productName,
                'provider_organization_name' => $this->provider_organization_name
            ]))
            ->view('emails.funds.product_reserved', [
                'product_name' => $this->productName,
                'product_price' => $this->productPrice,
                'provider_organization_name' => $this->provider_organization_name,
                'expire_at_minus_1_day' => $this->expire_at_minus_1_day,
                'qr_token' => $this->qr_token,
                'provider_phone' => $this->provider_phone,
                'provider_email' => $this->provider_email,
            ]);
    }
}
