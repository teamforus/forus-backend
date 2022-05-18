<?php

namespace App\Mail\User;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\Mime\Email;

/**
 * Class EmailActivationMail
 * @package App\Mail\User
 */
class EmailActivationMail extends ImplementationMail
{
    protected string $subjectKey = 'mails/system_mails.email_activation.title';

    /**
     * @return Mailable
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
    #[ArrayShape(['link' => "string", 'button' => "string", 'platform' => "string"])]
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
