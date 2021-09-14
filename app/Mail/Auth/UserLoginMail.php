<?php

namespace App\Mail\Auth;

use App\Mail\ImplementationMail;

/**
 * Class UserLoginMail
 * @package App\Mail\Auth
 */
class UserLoginMail extends ImplementationMail
{
    protected $subjectKey = 'mails/login_via_email.subject_title';
    protected $viewKey = 'emails.login.user-login';
}
