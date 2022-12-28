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
     * @param ReimbursementCreated $reimbursementCreated
     * @noinspection PhpUnused
     */
    public function onReimbursementCreated(ReimbursementCreated $reimbursementCreated): void
    {
        $reimbursement = $reimbursementCreated->getReimbursement();

        $reimbursement->log(
            $reimbursement::EVENT_CREATED,
            $this->getReimbursementLogModels($reimbursement)
        );
    }

    /**
     * @param ReimbursementSubmitted $reimbursementSubmitted
     * @throws \Exception
     * @noinspection PhpUnused
     */
    public function onReimbursementSubmitted(ReimbursementSubmitted $reimbursementSubmitted): void
    {
        $reimbursement = $reimbursementSubmitted->getReimbursement();

        IdentityReimbursementSubmittedNotification::send($reimbursement->log(
            $reimbursement::EVENT_SUBMITTED,
            $this->getReimbursementLogModels($reimbursement)
        ));
    }

    /**
     * @param ReimbursementResolved $fundCreated
     * @noinspection PhpUnused
     */
    public function onReimbursementResolved(ReimbursementResolved $fundCreated): void
    {
        if (!$fundCreated->getReimbursement()->isResolved()) {
            return;
        }

        $reimbursement = $fundCreated->getReimbursement();
        $eventModels = $this->getReimbursementLogModels($reimbursement);
        $eventLog = $reimbursement->log($reimbursement::EVENT_RESOLVED, $eventModels);

        if ($reimbursement->isApproved()) {
            $reimbursement->log($reimbursement::EVENT_APPROVED, $eventModels);
            IdentityReimbursementApprovedNotification::send($eventLog);
        } else {
            $reimbursement->log($reimbursement::EVENT_DECLINED, $eventModels);
            IdentityReimbursementDeclinedNotification::send($eventLog);
        }
    }

    /**
     * @param ReimbursementAssigned $event
     * @noinspection PhpUnused
     */
    public function onReimbursementAssigned(ReimbursementAssigned $event): void
    {
        $reimbursement = $event->getReimbursement();
        $supervisorEmployee = $event->getSupervisorEmployee();

        $eventModels = $this->getReimbursementLogModels($reimbursement, [
            'employee' => $event->getEmployee(),
        ]);

        $reimbursement->log($reimbursement::EVENT_ASSIGNED, $eventModels, array_merge(
            $supervisorEmployee ? $this->getSupervisorFields($supervisorEmployee) : [],
        ));
    }

    /**
     * @param ReimbursementResigned $event
     * @noinspection PhpUnused
     */
    public function onReimbursementResigned(ReimbursementResigned $event): void
    {
        $reimbursement = $event->getReimbursement();
        $supervisorEmployee = $event->getSupervisorEmployee();

        $eventModels = $this->getReimbursementLogModels($reimbursement, [
            'employee' => $event->getEmployee(),
        ]);

        $reimbursement->log($reimbursement::EVENT_RESIGNED, $eventModels, array_merge(
            $supervisorEmployee ? $this->getSupervisorFields($supervisorEmployee) : [],
        ));
    }

    /**
     * @param Reimbursement $reimbursement
     * @param array $extraModels
     * @return array
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
     * @return array
     */
    private function getSupervisorFields(?Employee $supervisor): array
    {
        return [
            'supervisor_employee_id' => $supervisor->id,
            'supervisor_employee_roles' => $supervisor->roles->pluck('name')->join(', '),
            'supervisor_employee_email' => $supervisor->identity?->email,
        ];
    }

    /**
     * The events dispatcher
     *
     * @param Dispatcher $events
     * @noinspection PhpUnused
     */
    public function subscribe(Dispatcher $events): void
    {
        $class = '\\' . static::class;

        $events->listen(ReimbursementCreated::class, "$class@onReimbursementCreated");
        $events->listen(ReimbursementSubmitted::class, "$class@onReimbursementSubmitted");
        $events->listen(ReimbursementResolved::class, "$class@onReimbursementResolved");

        $events->listen(ReimbursementAssigned::class, "$class@onReimbursementAssigned");
        $events->listen(ReimbursementResigned::class, "$class@onReimbursementResigned");
    }
}
