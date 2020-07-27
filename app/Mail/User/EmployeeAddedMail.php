<?php

namespace App\Mail\User;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;
use Illuminate\Mail\Mailable;

class EmployeeAddedMail extends ImplementationMail
{
    private $transData;

    /**
     * Create a new message instance.
     *
     * EmployeeAddedMail constructor.
     * @param array $data
     * @param EmailFrom|null $emailFrom
     */
    public function __construct(
        array $data = [],
        ?EmailFrom $emailFrom = null
    ) {
        $this->setMailFrom($emailFrom);
        $this->transData['data'] = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): Mailable
    {
        return $this->buildBase()
            ->subject(mail_trans('email_employee.title', $this->transData['data']))
            ->view('emails.user.employee_added', $this->transData['data']);
    }
}
