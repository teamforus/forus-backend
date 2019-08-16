<?php

namespace App\Mail\User;

use App\Mail\ImplementationMail;

class EmailActivation extends ImplementationMail
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
        return $this
            ->from(config('forus.mail.from.no-reply'))
            ->to($this->email)
            ->subject(implementation_trans('email_activation.title'))
            ->view('emails.user.email_activation', [
                'link' => $this->link
            ]);
    }
}
