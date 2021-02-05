<?php

namespace App\Notifications\Identities\Employee;

use App\Mail\User\EmployeeAddedMail;
use App\Models\Implementation;
use App\Services\EventLogService\Models\EventLog;
use App\Services\Forus\Identity\Models\Identity;

/**
 * Class IdentityAddedEmployeeNotification
 * @package App\Notifications\Identities\Employee
 */
class IdentityAddedEmployeeNotification extends BaseIdentityEmployeeNotification
{
    protected $key = 'notifications_identities.added_employee';
    protected $sendMail = true;

    /**
     * IdentityAddedEmployeeNotification constructor.
     * @param EventLog $eventLog
     * @param array $meta
     */
    public function __construct(EventLog $eventLog, array $meta = [])
    {
        parent::__construct($eventLog, $meta);
        $this->sendMail = !env('DISABLE_EMPLOYEE_INVITATION_EMAIL');
    }

    /**
     * @param Identity $identity
     * @throws \Exception
     */
    public function toMail(Identity $identity): void
    {
        $client_type = $this->eventLog->data['client_type'] ?? client_type();
        $implementation_key = $this->eventLog->data['implementation_key'] ?? implementation_key();

        $confirmationLink = sprintf(
            "%s/confirmation/email/%s",
            rtrim(Implementation::byKey($implementation_key)['url_' . $client_type], '/'),
            identity_repo()->makeIdentityPoxy($identity->address)['exchange_token']
        );

        notification_service()->sendMailNotification(
            $identity->primary_email->email,
            new EmployeeAddedMail(array_merge($this->eventLog->data, [
                'confirmationLink' => $confirmationLink,
                'link'             => 'https://www.forus.io/DL',
            ]), Implementation::emailFrom())
        );
    }
}
