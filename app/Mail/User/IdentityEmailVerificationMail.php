<?php

namespace App\Mail\User;

use App\Mail\ImplementationMail;

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
     */
    public function __construct(string $link) {
        parent::__construct(null);
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
