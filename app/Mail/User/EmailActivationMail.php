<?php

namespace App\Mail\User;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;
use Illuminate\Mail\Mailable;

/**
 * Class EmailActivationMail
 * @package App\Mail\User
 */
class EmailActivationMail extends ImplementationMail
{
    private $clientType;
    private $link;

    public function __construct(
        string $clientType,
        string $link,
        ?EmailFrom $emailFrom
    ) {
        $this->setMailFrom($emailFrom);
        $this->clientType = $clientType;
        $this->link = $link;
    }

    public function build(): Mailable
    {
        return $this->buildBase()
            ->subject(mail_trans('email_activation.title'))
            ->view('emails.user.email_activation', [
                'link'          => $this->link,
                'clientType'    => $this->clientType,
            ]);
    }
}
