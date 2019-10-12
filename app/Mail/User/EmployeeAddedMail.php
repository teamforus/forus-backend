<?php

namespace App\Mail\User;

use App\Mail\ImplementationMail;

class EmployeeAddedMail extends ImplementationMail
{
    private $platform;
    private $confirmationLink;
    private $link;

    /**
     * Create a new message instance.
     *
     * EmployeeAddedMail constructor.
     * @param string $platform
     * @param string $confirmationLink
     * @param string $identityId
     */
    public function __construct(
        string $platform,
        string $confirmationLink,
        ?string $identityId
    ) {
        parent::__construct($identityId);

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
            ->subject(mail_trans('email_employee.title'))
            ->view('emails.user.employee_added', [
                'confirmationLink' => $this->confirmationLink,
                'orgName' => '',
                'link'    => $this->link,
            ]);
    }
}
