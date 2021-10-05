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
     * @noinspection PhpUnused
     */
    public function onPhysicalCardRequestsCreated(
        PhysicalCardRequestsCreated $physicalCardRequestCreated
    ): void {
        $physicalCardRequest = $physicalCardRequestCreated->getPhysicalCardRequest();

        $address = $physicalCardRequest->address . ' ' . implode(', ', array_filter([
            $physicalCardRequest->house,
            $physicalCardRequest->house_addition,
            $physicalCardRequest->postcode,
            $physicalCardRequest->city
        ]));

        $eventLog = $physicalCardRequest->log(PhysicalCardRequest::EVENT_CREATED, [
            'physical_card_request'     => $physicalCardRequest,
            'employee'                  => $physicalCardRequest->employee,
            'voucher'                   => $physicalCardRequest->voucher,
            'sponsor'                   => $physicalCardRequest->voucher->fund->organization,
            'fund'                      => $physicalCardRequest->voucher->fund,
        ], [
            'note'    => "Adresgegevens: $address",
            'address' => $address,
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
