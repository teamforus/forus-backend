<?php

namespace App\Listeners;

use App\Events\PhysicalCardRequests\PhysicalCardRequestsCreated;
use App\Models\PhysicalCardRequest;
use App\Notifications\Identities\PhysicalCardRequest\PhysicalCardRequestCreatedNotification;
use App\Notifications\Organizations\PhysicalCardRequest\PhysicalCardRequestCreatedSponsorNotification;
use Illuminate\Events\Dispatcher;

/**
 * Class ProductSubscriber
 * @package App\Listeners
 */
class PhysicalCardRequestsSubscriber
{
    /**
     * @param PhysicalCardRequestsCreated $physicalCardRequestCreated
     */
    public function onPhysicalCardRequestsCreated(PhysicalCardRequestsCreated $physicalCardRequestCreated): void
    {
        $physicalCardRequest = $physicalCardRequestCreated->getPhysicalCardRequest();
        $voucher = $physicalCardRequestCreated->getVoucher();
        $employee = $voucher->fund->organization->findEmployee(auth_address());

        $address = sprintf(
            "%s %s, %s, %s, %s",
            $physicalCardRequest->address,
            $physicalCardRequest->house,
            $physicalCardRequest->house_addition,
            $physicalCardRequest->postcode,
            $physicalCardRequest->city
        );

        $eventLog = $physicalCardRequest->log(PhysicalCardRequest::EVENT_CREATED, [
            'physical_card_request'  => $physicalCardRequest,
            'voucher'                => $voucher,
            'sponsor'                => $voucher->fund->organization,
            'fund'                   => $voucher->fund
        ], [
            'note'    => $address,
            'address' => $address,
            'employee_id' => $employee ? $employee->id : null,
        ]);

        PhysicalCardRequestCreatedNotification::send($eventLog);
        PhysicalCardRequestCreatedSponsorNotification::send($eventLog);
    }

    /**
     * The events dispatcher
     *
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            PhysicalCardRequestsCreated::class,
            '\App\Listeners\PhysicalCardRequestsSubscriber@onPhysicalCardRequestsCreated'
        );
    }
}
