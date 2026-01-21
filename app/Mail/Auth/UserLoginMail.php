<?php

namespace App\Mail\Auth;

use App\Mail\ImplementationMail;
use Carbon\Carbon;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class UserLoginMail extends ImplementationMail
{
    public $subject = 'Log in op :platform geldig tot :time';

    /**
     * @throws CommonMarkException
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
            'time' => Carbon::now()->addHour()->translatedFormat('j F H:i'),
            'auth_button' => $this->makeButton($data['auth_link'], 'INLOGGEN'),
        ];
    }
}
