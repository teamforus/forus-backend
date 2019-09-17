<?php

namespace App\Mail\User;

use App\Mail\ImplementationMail;

/**
 * Class EmailActivationMail
 * @package App\Mail\User
 */
class EmailActivationMail extends ImplementationMail
{
    private $platform;
    private $link;

    public function __construct(
        string $email,
        string $platform,
        string $link,
        ?string $identityId
    ) {
        parent::__construct($email, $identityId);

        $this->platform = $platform;
        $this->link = $link;
    }

    public function build(): ImplementationMail
    {
        return parent::build()
            ->subject(mail_trans('email_activation.title'))
            ->view('emails.user.email_activation', [
                'link' => $this->link
            ]);
    }
}
