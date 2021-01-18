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
        $xSesConfigurationSet = env('MAIL_X_SES_CONFIGURATION_SET', false);

        if ($xSesConfigurationSet) {
            $this->withSwiftMessage(function ($message) use ($xSesConfigurationSet) {
                $message->getHeaders()->addTextHeader('X-SES-CONFIGURATION-SET', $xSesConfigurationSet);
            });
        }

        return $this->buildBase()
            ->subject(mail_trans('email_activation.title'))
            ->view('emails.user.email_activation', [
                'link'          => $this->link,
                'clientType'    => $this->clientType,
            ]);
    }
}
