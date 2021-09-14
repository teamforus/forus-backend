<?php

namespace App\Notifications\Identities\Employee;

use App\Mail\User\EmployeeAddedMail;
use App\Models\Implementation;
use App\Services\Forus\Identity\Models\Identity;

class IdentityAddedEmployeeNotification extends BaseIdentityEmployeeNotification
{
    protected static $key = 'notifications_identities.added_employee';
    protected static $pushKey = 'employee.created';
    protected static $sendMail = true;
    protected static $sendPush = true;

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

        $this->sendMailNotification(
            $identity->primary_email->email,
            new EmployeeAddedMail(array_merge($this->eventLog->data, [
                'dashboard_auth_link'   => $confirmationLink,
                'download_me_app_link'  => 'https://www.forus.io/DL',
            ]), Implementation::emailFrom())
        );
    }
}
