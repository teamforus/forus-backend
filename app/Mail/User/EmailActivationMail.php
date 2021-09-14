<?php

namespace App\Mail\User;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

/**
 * Class EmailActivationMail
 * @package App\Mail\User
 */
class EmailActivationMail extends ImplementationMail
{
    protected $subjectKey = "mails/email_activation.title";
    protected $viewKey = "emails.user.email_activation";

    public function build(): Mailable
    {
        $xSesConfigurationSet = env('MAIL_X_SES_CONFIGURATION_SET', false);

        if ($xSesConfigurationSet) {
            $this->withSwiftMessage(function ($message) use ($xSesConfigurationSet) {
                $message->getHeaders()->addTextHeader('X-SES-CONFIGURATION-SET', $xSesConfigurationSet);
            });
        }

        return parent::build();
    }
}
