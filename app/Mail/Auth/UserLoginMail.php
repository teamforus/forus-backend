<?php

namespace App\Mail\Auth;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

/**
 * Class UserLoginMail
 * @package App\Mail\Auth
 */
class UserLoginMail extends ImplementationMail
{
    protected $subjectKey = 'mails/system_mails.user_login.title';

    /**
     * @return Mailable
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
            'auth_button'   => $this->makeButton($data['link'], 'INLOGGEN'),
        ];
    }
}
