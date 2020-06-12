<?php

namespace App\Mail\Auth;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;
use Illuminate\Mail\Mailable;

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
        ?EmailFrom $emailFrom
    ) {
        $this->setMailFrom($emailFrom);

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

    public function build(): Mailable
    {
        return $this->buildBase()
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
