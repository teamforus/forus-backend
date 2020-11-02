<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;
use Illuminate\Mail\Mailable;

class RequestPhysicalCardMail extends ImplementationMail
{
    private $data;

    /**
     * RequestPhysicalCardMail constructor.
     *
     * RequestPhysicalCardMail constructor.
     * @param EmailFrom|null $email_from
     * @param array $data
     */
    public function __construct(
        ?EmailFrom $email_from,
        array $data = []
    ) {
        $this->data = $this->escapeData($data);
        $this->setMailFrom($email_from);
    }

    public function build(): Mailable
    {
        return $this->buildBase()
            ->subject(mail_trans('request_physical_card.title', []))
            ->view('emails.vouchers.request-physical-card', [
                'data' => $this->data
            ]);
    }
}
