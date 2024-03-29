<?php

namespace App\Mail\User;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class IdentityEmailVerificationMail extends ImplementationMail
{
    protected string $subjectKey = 'mails/system_mails.identity_email_verification.title';

    /**
     * @return Mailable
     * @throws CommonMarkException
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
