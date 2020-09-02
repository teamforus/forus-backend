<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;
use Illuminate\Mail\Mailable;

class RequestPhysicalCardMail extends ImplementationMail
{
    private $postcode;
    private $houseNumber;

    /**
     * RequestPhysicalCardMail constructor.
     * @param string $postcode
     * @param string $houseNumber
     * @param EmailFrom|null $emailFrom
     */
    public function __construct(
        string $postcode,
        string $houseNumber,
        ?EmailFrom $emailFrom
    ){
        $this->setMailFrom($emailFrom);

        $this->postcode = $postcode;
        $this->houseNumber = $houseNumber;
    }

    public function build(): Mailable
    {
        return $this->buildBase()
            ->subject(mail_trans('request_physical_card.title', []))
            ->view('emails.vouchers.request-physical-card', [
                'postcode'      => $this->postcode,
                'houseNumber'   => $this->houseNumber,
            ]);
    }
}
