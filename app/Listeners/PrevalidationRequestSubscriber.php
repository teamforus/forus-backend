<?php

namespace App\Listeners;

use App\Events\PrevalidationRequests\PrevalidationRequestCreatedEvent;
use App\Events\PrevalidationRequests\PrevalidationRequestDeletedEvent;
use App\Events\PrevalidationRequests\PrevalidationRequestFailedEvent;
use App\Events\PrevalidationRequests\PrevalidationRequestStateResubmittedEvent;
use App\Events\PrevalidationRequests\PrevalidationRequestStateUpdatedEvent;
use App\Models\PrevalidationRequest;
use Exception;
use Illuminate\Events\Dispatcher;

class PrevalidationRequestSubscriber
{
    /**
     * @param PrevalidationRequestCreatedEvent $event
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function onPrevalidationRequestCreated(PrevalidationRequestCreatedEvent $event): void
    {
        $prevalidationRequest = $event->getPrevalidationRequest();

        $prevalidationRequest->log(PrevalidationRequest::EVENT_CREATED, [
            'prevalidation_request' => $prevalidationRequest,
            'organization' => $prevalidationRequest->organization,
        ], [
            ...$event->getResponseArray(),
        ]);
    }

    /**
     * @param PrevalidationRequestStateUpdatedEvent $event
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function onPrevalidationRequestUpdated(PrevalidationRequestStateUpdatedEvent $event): void
    {
        $prevalidationRequest = $event->getPrevalidationRequest();

        $prevalidationRequest->log(PrevalidationRequest::EVENT_UPDATED, [
            'prevalidation_request' => $prevalidationRequest,
            'organization' => $prevalidationRequest->organization,
        ], [
            'prevalidation_request_previous_state' => $event->getPreviousState(),
            ...$event->getResponseArray(),
        ]);
    }

    /**
     * @param PrevalidationRequestFailedEvent $event
     */
    public function onPrevalidationRequestFailed(PrevalidationRequestFailedEvent $event): void
    {
        $prevalidationRequest = $event->getPrevalidationRequest();

        $prevalidationRequest->log(PrevalidationRequest::EVENT_FAILED, [
            'prevalidation_request' => $prevalidationRequest,
            'organization' => $prevalidationRequest->organization,
        ], [
            'prevalidation_request_fail_reason' => $event->getReason(),
            ...$event->getResponseArray(),
        ]);
    }

    /**
     * @param PrevalidationRequestStateResubmittedEvent $event
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function onPrevalidationRequestResubmitted(PrevalidationRequestStateResubmittedEvent $event): void
    {
        $prevalidationRequest = $event->getPrevalidationRequest();

        $prevalidationRequest->log(PrevalidationRequest::EVENT_RESUBMITTED, [
            'prevalidation_request' => $prevalidationRequest,
            'organization' => $prevalidationRequest->organization,
        ], [
            'prevalidation_request_previous_state' => $event->getPreviousState(),
            ...$event->getResponseArray(),
        ]);
    }

    /**
     * @param PrevalidationRequestDeletedEvent $event
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function onPrevalidationRequestDeleted(PrevalidationRequestDeletedEvent $event): void
    {
        $prevalidationRequest = $event->getPrevalidationRequest();

        $prevalidationRequest->log(PrevalidationRequest::EVENT_DELETED, [
            'prevalidation_request' => $prevalidationRequest,
            'organization' => $prevalidationRequest->organization,
        ], [
            ...$event->getResponseArray(),
        ]);
    }

    /**
     * The events dispatcher.
     *
     * @param Dispatcher $events
     * @noinspection PhpUnused
     */
    public function subscribe(Dispatcher $events): void
    {
        $class = '\\' . static::class;

        $events->listen(PrevalidationRequestCreatedEvent::class, "$class@onPrevalidationRequestCreated");
        $events->listen(PrevalidationRequestStateUpdatedEvent::class, "$class@onPrevalidationRequestUpdated");
        $events->listen(PrevalidationRequestFailedEvent::class, "$class@onPrevalidationRequestFailed");
        $events->listen(PrevalidationRequestDeletedEvent::class, "$class@onPrevalidationRequestDeleted");
        $events->listen(PrevalidationRequestStateResubmittedEvent::class, "$class@onPrevalidationRequestResubmitted");
    }
}
