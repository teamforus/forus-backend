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
    private $platform;
    private $link;

    public function __construct(
        string $source,
        string $link,
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
            $platform = 'de website';
        } else if (strpos($source, 'me_app') !== false) {
            $platform = 'Me';
        }

        $this->platform = $platform;
        $this->link = $link;
    }

    public function build(): Mailable
    {
        return $this->buildBase()
            ->subject(mail_trans('email_activation.title'))
            ->view('emails.user.email_activation', [
                'link'      => $this->link,
                'platform'  => $this->platform
            ]);
    }
}
