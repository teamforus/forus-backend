<?php

namespace App\Listeners;

use App\Events\PrevalidationRequests\PrevalidationRequestCreated;
use App\Events\PrevalidationRequests\PrevalidationRequestDeleted;
use App\Events\PrevalidationRequests\PrevalidationRequestFailed;
use App\Events\PrevalidationRequests\PrevalidationRequestStateResubmitted;
use App\Events\PrevalidationRequests\PrevalidationRequestStateUpdated;
use App\Models\PrevalidationRequest;
use Exception;
use Illuminate\Events\Dispatcher;

class PrevalidationRequestSubscriber
{
    /**
     * @param PrevalidationRequestCreated $prevalidationRequestCreated
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function onPrevalidationRequestCreated(PrevalidationRequestCreated $prevalidationRequestCreated): void
    {
        $prevalidationRequest = $prevalidationRequestCreated->getPrevalidationRequest();

        $prevalidationRequest->log(PrevalidationRequest::EVENT_CREATED, [
            'prevalidation_request' => $prevalidationRequest,
            'organization' => $prevalidationRequest->organization,
        ]);
    }

    /**
     * @param PrevalidationRequestStateUpdated $prevalidationRequestUpdated
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function onPrevalidationRequestUpdated(PrevalidationRequestStateUpdated $prevalidationRequestUpdated): void
    {
        $prevalidationRequest = $prevalidationRequestUpdated->getPrevalidationRequest();

        $prevalidationRequest->log(PrevalidationRequest::EVENT_UPDATED, [
            'prevalidation_request' => $prevalidationRequest,
            'organization' => $prevalidationRequest->organization,
        ], [
            'prevalidation_request_previous_state' => $prevalidationRequestUpdated->getPreviousState(),
        ]);
    }

    /**
     * @param PrevalidationRequestFailed $prevalidationRequestFailed
     */
    public function onPrevalidationRequestFailed(PrevalidationRequestFailed $prevalidationRequestFailed): void
    {
        $prevalidationRequest = $prevalidationRequestFailed->getPrevalidationRequest();

        $prevalidationRequest->log(PrevalidationRequest::EVENT_FAILED, [
            'prevalidation_request' => $prevalidationRequest,
            'organization' => $prevalidationRequest->organization,
        ], [
            'prevalidation_request_fail_reason' => $prevalidationRequestFailed->getReason(),
        ]);
    }

    /**
     * @param PrevalidationRequestStateResubmitted $prevalidationRequestResubmitted
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function onPrevalidationRequestResubmitted(PrevalidationRequestStateResubmitted $prevalidationRequestResubmitted): void
    {
        $prevalidationRequest = $prevalidationRequestResubmitted->getPrevalidationRequest();

        $prevalidationRequest->log(PrevalidationRequest::EVENT_RESUBMITTED, [
            'prevalidation_request' => $prevalidationRequest,
            'organization' => $prevalidationRequest->organization,
        ], [
            'prevalidation_request_previous_state' => $prevalidationRequestResubmitted->getPreviousState(),
        ]);
    }

    /**
     * @param PrevalidationRequestDeleted $prevalidationRequestDeleted
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function onPrevalidationRequestDeleted(PrevalidationRequestDeleted $prevalidationRequestDeleted): void
    {
        $prevalidationRequest = $prevalidationRequestDeleted->getPrevalidationRequest();

        $prevalidationRequest->log(PrevalidationRequest::EVENT_DELETED, [
            'prevalidation_request' => $prevalidationRequest,
            'organization' => $prevalidationRequest->organization,
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

        $events->listen(PrevalidationRequestCreated::class, "$class@onPrevalidationRequestCreated");
        $events->listen(PrevalidationRequestStateUpdated::class, "$class@onPrevalidationRequestUpdated");
        $events->listen(PrevalidationRequestFailed::class, "$class@onPrevalidationRequestFailed");
        $events->listen(PrevalidationRequestDeleted::class, "$class@onPrevalidationRequestDeleted");
        $events->listen(PrevalidationRequestStateResubmitted::class, "$class@onPrevalidationRequestResubmitted");
    }
}
