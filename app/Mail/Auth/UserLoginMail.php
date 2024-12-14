<?php

namespace App\Mail\Auth;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class UserLoginMail extends ImplementationMail
{
    protected string $subjectKey = 'mails/system_mails.user_login.title';

    /**
     * @return Mailable
     * @throws CommonMarkException
     */
    public function build(): Mailable
    {
        return $this->buildSystemMail('user_login');
    }

    /**
     * @param array $data
     * @return array
     */
    protected function getMailExtraData(array $data): array
    {
        return [
            'time'          => strftime('%e %B %H:%M', strtotime("+1 hours")),
            'auth_button'   => $this->makeButton($data['auth_link'], 'INLOGGEN'),
        ];
    }
}
