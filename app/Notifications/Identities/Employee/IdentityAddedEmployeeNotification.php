<?php

namespace App\Notifications\Identities\Employee;

use App\Http\Requests\BaseFormRequest;
use App\Mail\User\EmployeeAddedMail;
use App\Models\Identity;
use App\Models\Implementation;

/**
 * Notify identity about them becoming an employee for the organization
 */
class IdentityAddedEmployeeNotification extends BaseIdentityEmployeeNotification
{
    protected static ?string $key = 'notifications_identities.added_employee';
    protected static ?string $pushKey = 'employee.created';

    /**
     * @param Identity $identity
     * @throws \Exception
     */
    public function toMail(Identity $identity): void
    {
        $request = BaseFormRequest::createFrom(request());
        $client_type = $this->eventLog->data['client_type'] ?? $request->client_type();
        $implementation_key = $this->eventLog->data['implementation_key'] ?? $request->implementation_key();

        $confirmationLink = sprintf(
            "%s/confirmation/email/%s",
            rtrim(Implementation::byKey($implementation_key)['url_' . $client_type], '/'),
            $identity->makeIdentityPoxy()->exchange_token,
        );

        $mailable = new EmployeeAddedMail([
            ...$this->eventLog->data,
            'dashboard_auth_link' => $confirmationLink,
            'download_me_app_link' => 'https://www.forus.io/DL',
        ], Implementation::emailFrom());

        $this->sendMailNotification($identity->email, $mailable, $this->eventLog);
    }
}
