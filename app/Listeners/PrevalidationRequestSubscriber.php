<?php

namespace App\Listeners;

use App\Events\PrevalidationRequests\PrevalidationRequestCreated;
use App\Events\PrevalidationRequests\PrevalidationRequestDeleted;
use App\Events\PrevalidationRequests\PrevalidationRequestFailed;
use App\Events\PrevalidationRequests\PrevalidationRequestResubmitted;
use App\Events\PrevalidationRequests\PrevalidationRequestUpdated;
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
     * @param PrevalidationRequestUpdated $prevalidationRequestUpdated
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function onPrevalidationRequestUpdated(PrevalidationRequestUpdated $prevalidationRequestUpdated): void
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
            'prevalidation_request_failed_reason' => $prevalidationRequestFailed->getFailedReason(),
        ]);
    }

    /**
     * @param PrevalidationRequestResubmitted $prevalidationRequestResubmitted
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function onPrevalidationRequestResubmitted(PrevalidationRequestResubmitted $prevalidationRequestResubmitted): void
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
        $events->listen(PrevalidationRequestUpdated::class, "$class@onPrevalidationRequestUpdated");
        $events->listen(PrevalidationRequestFailed::class, "$class@onPrevalidationRequestFailed");
        $events->listen(PrevalidationRequestDeleted::class, "$class@onPrevalidationRequestDeleted");
        $events->listen(PrevalidationRequestResubmitted::class, "$class@onPrevalidationRequestResubmitted");
    }
}
