<?php

namespace App\Notifications\Identities\Employee;

use App\Helpers\Arr;
use App\Mail\User\FundRequestAssignedBySupervisorMail;
use App\Models\Identity;
use App\Models\Implementation;

/**
 * Notify identity about them being removed from an organization
 */
class IdentityAssignedToFundRequestBySupervisorNotification extends BaseIdentityEmployeeNotification
{
    protected static ?string $key = 'notifications_identities.assigned_to_fund_request_by_supervisor';

    public function toMail(Identity $identity): void
    {
        if (!$identity->email) {
            return;
        }

        $buttonLink = Implementation::active()->urlValidatorDashboard(sprintf(
            '/organizations/%d/requests/%d',
            Arr::get($this->eventLog->data, 'employee_organization_id'),
            Arr::get($this->eventLog->data, 'fund_request_id'),
        ));

        $mailData = array_merge($this->eventLog->data, [
            'button_link' => $buttonLink,
            'supervisor_assigned_at'=> now()->format('Y-m-d H:i:s'),
            'supervisor_assigned_at_locale'=> format_datetime_locale(now()),
        ]);

        $this->sendMailNotification($identity->email, new FundRequestAssignedBySupervisorMail($mailData));
    }
}
