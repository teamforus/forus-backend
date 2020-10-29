<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;
use Illuminate\Mail\Mailable;

class RequestPhysicalCardMail extends ImplementationMail
{
    private $postcode;
    private $house_number;
    private $city;
    private $street_name;
    private $fund_name;
    private $sponsor_phone;
    private $sponsor_email;

    /**
     * RequestPhysicalCardMail constructor.
     * @param string $postcode
     * @param string $house_number
     * @param string $city
     * @param string $street_name
     * @param string $fund_name
     * @param string $sponsor_phone
     * @param string $sponsor_email
     * @param EmailFrom|null $email_from
     */
    public function __construct(
        string $postcode,
        string $house_number,
        string $city,
        string $street_name,
        string $fund_name,
        string $sponsor_phone,
        string $sponsor_email,
        ?EmailFrom $email_from
    ) {
        $this->setMailFrom($email_from);

        $this->postcode      = $postcode;
        $this->house_number  = $house_number;
        $this->city          = $city;
        $this->street_name   = $street_name;
        $this->fund_name     = $fund_name;
        $this->sponsor_phone = $sponsor_phone;
        $this->sponsor_email = $sponsor_email;
    }

    public function build(): Mailable
    {
        return $this->buildBase()
            ->subject(mail_trans('request_physical_card.title', []))
            ->view('emails.vouchers.request-physical-card', [
                'postcode'      => $this->postcode,
                'house_number'  => $this->house_number,
                'city'          => $this->city,
                'street_name'   => $this->street_name,
                'fund_name'     => $this->fund_name,
                'sponsor_phone' => $this->sponsor_phone,
                'sponsor_email' => $this->sponsor_email,
            ]);
    }
}
