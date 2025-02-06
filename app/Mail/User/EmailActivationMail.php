<?php

namespace App\Mail\User;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;
use Symfony\Component\Mime\Email;

class EmailActivationMail extends ImplementationMail
{
    public $subject = 'E-mailadres bevestigen';

    /**
     * @return Mailable
     * @throws CommonMarkException
     */
    public function build(): Mailable
    {
        $xSesConfigurationSet = env('MAIL_X_SES_CONFIGURATION_SET', false);

        if ($xSesConfigurationSet) {
            $this->withSymfonyMessage(function (Email $message) use ($xSesConfigurationSet) {
                $message->getHeaders()->addTextHeader('X-SES-CONFIGURATION-SET', $xSesConfigurationSet);
            });
        }

        return parent::buildSystemMail('email_activation');
    }

    /**
     * @param array $data
     * @return array
     */
    protected function getMailExtraData(array $data): array
    {
        $platform = [
            'webshop' => 'de webshop',
            'sponsor' => 'het aanmeldformulier voor sponsoren',
            'provider' => 'het aanmeldformulier voor aanbieders',
            'validator' => 'het aanmeldformulier voor validators',
            'website' => 'de website',
            'me_app-android' => 'de Me-app',
            'me_app-ios' => 'de Me-app',
        ];

        return [
            'link' => $this->makeLink($data['link'], 'link'),
            'button' => $this->makeButton($data['link'], 'BEVESTIGEN'),
            'platform' => $platform[$data['clientType'] ?? ''] ?? '',
        ];
    }
}
