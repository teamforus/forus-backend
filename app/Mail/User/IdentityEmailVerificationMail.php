<?php

namespace App\Mail\User;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class IdentityEmailVerificationMail extends ImplementationMail
{
    public $subject = 'E-mailadres bevestigen';

    /**
     * @throws CommonMarkException
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildSystemMail('identity_email_verification');
    }

    /**
     * @param array $data
     * @return array
     */
    protected function getMailExtraData(array $data): array
    {
        return [
            'link' => $this->makeLink($data['link'], 'link'),
            'button' => $this->makeButton($data['link'], 'BEVESTIGEN'),
        ];
    }
}
