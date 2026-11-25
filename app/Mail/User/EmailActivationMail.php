<?php

namespace App\Mail\User;

use App\Mail\ImplementationMail;
use App\Models\Implementation;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;
use Symfony\Component\Mime\Email;

class EmailActivationMail extends ImplementationMail
{
    public $subject = 'E-mailadres bevestigen';

    /**
     * @throws CommonMarkException
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
    protected function getMailExtraData(array $data): array
    {
        $platform = [
            'webshop' => 'de webshop',
            'sponsor' => 'het aanmeldformulier voor sponsoren',
            'provider' => 'het aanmeldformulier voor aanbieders',
            'validator' => 'het aanmeldformulier voor validators',
            'website' => 'de website',
            Implementation::ME_APP_ANDROID => 'de Me-app',
            Implementation::ME_APP_IOS => 'de Me-app',
            Implementation::ME_APP_REQUESTER_ANDROID => 'de Me-app',
            Implementation::ME_APP_REQUESTER_IOS => 'de Me-app',
            Implementation::ME_APP_PROVIDER_ANDROID => 'de Me-app',
            Implementation::ME_APP_PROVIDER_IOS => 'de Me-app',
        ];

        return [
            'link' => $this->makeLink($data['link'], 'link'),
            'button' => $this->makeButton($data['link'], 'BEVESTIGEN'),
            'platform' => $platform[$data['clientType'] ?? ''] ?? '',
        ];
    }
}
