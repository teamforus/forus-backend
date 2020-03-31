<?php

namespace App\Mail\User;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;

class EmployeeAddedMail extends ImplementationMail
{
    private $orgName;
    private $platform;
    private $confirmationLink;
    private $link;

    /**
     * Create a new message instance.
     *
     * EmployeeAddedMail constructor.
     * @param string $orgName
     * @param string $platform
     * @param string $confirmationLink
     * @param EmailFrom|null $emailFrom
     */
    public function __construct(
        string $orgName,
        string $platform,
        string $confirmationLink,
        ?EmailFrom $emailFrom
    ) {
        parent::__construct($emailFrom);

        $this->orgName = $orgName;
        $this->platform = $platform;
        $this->confirmationLink = $confirmationLink;
        $this->link = 'https://www.forus.io/DL';
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): ImplementationMail
    {
        return parent::build()
            ->subject(mail_trans('email_employee.title', [
                'orgName' => $this->orgName
            ]))
            ->view('emails.user.employee_added', [
                'confirmationLink' => $this->confirmationLink,
                'orgName' => $this->orgName,
                'link'    => $this->link,
            ]);
    }
}
