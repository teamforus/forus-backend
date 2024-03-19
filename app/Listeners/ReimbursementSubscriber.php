<?php

namespace App\Listeners;

use App\Events\Reimbursements\ReimbursementCreated;
use App\Events\Reimbursements\ReimbursementSubmitted;
use App\Events\Reimbursements\ReimbursementAssigned;
use App\Events\Reimbursements\ReimbursementResigned;
use App\Events\Reimbursements\ReimbursementResolved;
use App\Models\Employee;
use App\Models\Reimbursement;
use App\Notifications\Identities\Reimbursement\IdentityReimbursementApprovedNotification;
use App\Notifications\Identities\Reimbursement\IdentityReimbursementDeclinedNotification;
use App\Notifications\Identities\Reimbursement\IdentityReimbursementSubmittedNotification;
use Illuminate\Events\Dispatcher;

class ReimbursementSubscriber
{


    /**
     * @param Reimbursement $reimbursement
     * @param array $extraModels
     *
     * @return (Reimbursement|\App\Models\Fund|\App\Models\Implementation|\App\Models\Organization|mixed)[]
     *
     * @psalm-return array{fund: \App\Models\Fund|mixed, sponsor: \App\Models\Organization|mixed, reimbursement: Reimbursement|mixed, implementation: \App\Models\Implementation|mixed,...}
     */
    private function getReimbursementLogModels(
        Reimbursement $reimbursement,
        array $extraModels = []
    ): array {
        return array_merge([
            'fund' => $reimbursement->voucher->fund,
            'sponsor' => $reimbursement->voucher->fund->organization,
            'reimbursement' => $reimbursement,
            'implementation' => $reimbursement->voucher->fund->getImplementation(),
        ], $extraModels);
    }

    /**
     * @param Employee|null $supervisor
     *
     * @return (int|null|string)[]
     *
     * @psalm-return array{supervisor_employee_id: int|null, supervisor_employee_roles: string, supervisor_employee_email: null|string}
     */
    private function getSupervisorFields(?Employee $supervisor): array
    {
        return [
            'supervisor_employee_id' => $supervisor->id,
            'supervisor_employee_roles' => $supervisor->roles->pluck('name')->join(', '),
            'supervisor_employee_email' => $supervisor->identity?->email,
        ];
    }
}
