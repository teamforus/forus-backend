<?php

namespace App\Mail\User;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;

/**
 * Class EmailActivationMail
 * @package App\Mail\User
 */
class IdentityEmailVerificationMail extends ImplementationMail
{
    private $link;

    /**
     * IdentityEmailVerificationMail constructor.
     * @param string $link
     * @param EmailFrom $emailFrom
     */
    public function __construct(string $link, ?EmailFrom $emailFrom) {
        parent::__construct($emailFrom);
        $this->link = $link;
    }

    /**
     * @return ImplementationMail
     */
    public function build(): ImplementationMail
    {
        return parent::build()
            ->subject(mail_trans('identity_email_verification.title'))
            ->view('emails.identity-email-verification', [
                'link' => $this->link
            ]);
    }
}
