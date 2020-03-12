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
        string $link,
        string $source,
        string $identityId = null
    ) {
        parent::__construct($identityId);

        $platform = '';

        if (strpos($source, '_webshop') !== false) {
            $platform = 'de webshop';
        } else if (strpos($source, '_sponsor') !== false) {
            $platform = 'het dashboard';
        } else if (strpos($source, '_provider') !== false) {
            $platform = 'het dashboard';
        } else if (strpos($source, '_validator') !== false) {
            $platform = 'het dashboard';
        } else if (strpos($source, '_website') !== false) {
            $platform = 'het website';
        } else if (strpos($source, 'me_app') !== false) {
            $platform = 'Me';
        }

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
