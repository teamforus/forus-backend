<?php

namespace App\Notifications\Identities\Employee;

use App\Mail\User\EmployeeAddedMail;
use App\Models\Implementation;
use App\Services\Forus\Identity\Models\Identity;

class IdentityAddedEmployeeNotification extends BaseIdentityEmployeeNotification
{
    protected $key = 'notifications_identities.added_employee';
    protected static $permissions = [
        'manage_employees'
    ];

    /**
     * @param Identity $identity
     * @return bool|null
     * @throws \Exception
     */
    public function toMail(Identity $identity)
    {
        $identityProxy = resolve('forus.services.identity')->makeIdentityPoxy(
            $identity->address
        );

        $confirmationLink = sprintf(
            "%s/confirmation/email/%s?%s",
            rtrim(Implementation::active()['url_' . client_type('general')], '/'),
            $identityProxy['exchange_token'],
            http_build_query(compact('target'))
        );

        return resolve('forus.services.notification')->sendMailNotification(
            $identity->primary_email->email,
            new EmployeeAddedMail(array_merge($this->eventLog->data, [
                'confirmationLink' => $confirmationLink,
                'link'             => 'https://www.forus.io/DL',
            ]), Implementation::emailFrom())
        );
    }
}
