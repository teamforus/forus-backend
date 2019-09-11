<?php

namespace App\Mail\Auth;

use App\Mail\ImplementationMail;

/**
 * Class UserLoginMail
 * @package App\Mail\Auth
 */
class UserLoginMail extends ImplementationMail
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
        return parent::build()
            ->subject(mail_trans('login_via_email.subject_title', [
                'platform' => $this->platform,
		'time' => date('H:i', strtotime('1 hour'))
            ]))
            ->view('emails.login.login_via_email', [
                'platform' => $this->platform,
                'link' => $this->link
            ]);
    }
}
