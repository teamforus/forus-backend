<?php

namespace App\Mail\Auth;

use App\Mail\ImplementationMail;

class UserLogin extends ImplementationMail
{
    /**
     * @var string $link
     */
    private $link;

    /**
     * @var string $platform
     */
    private $platform;

    public function __construct(
        string $email,
        string $link,
        string $platform,
        ?string $identityId
    ) {
        parent::__construct($email, $identityId);

        $this->link = $link;
        $this->platform = $platform;
    }

    public function build(): ImplementationMail
    {
        return $this
            ->from(config('forus.mail.from.no-reply'))
            ->to($this->email)
            ->subject('Inloggen op Forus')
            ->view('emails.login.login_via_email', [
                'platform' => $this->platform,
                'link' => $this->link
            ]);
    }
}
