<?php

namespace App\Modules\DCP\Listeners;

use App\Modules\DCP\Events\DCPLifecycleEvent;
use App\Modules\DCP\Models\DCPOrder;
use App\Modules\DCP\Services\DCPLogService;
use Illuminate\Events\Dispatcher;
use Throwable;

class DCPEventSubscriber
{
    /**
     * @param DCPLifecycleEvent $event
     * @return void
     */
    public function handle(DCPLifecycleEvent $event): void
    {
        $model = $event->model;

        try {
            $model->log($event->type->value, [], [
                'organization_id' => $model->organization_id,
                'dcp_order_id' => $model instanceof DCPOrder ? $model->id : null,
                'dcp_order_bulk_id' => $model instanceof DCPOrder ? $model->dcp_order_bulk_id : $model->id,
                'state' => $event->state?->value,
                'status_id' => $event->statusId,
                'previous_status_id' => $event->previousStatusId,
                'diagnostic_id' => $event->diagnosticId,
            ], $event->identityAddress, useRequestIdentity: false);
        } catch (Throwable $exception) {
            resolve(DCPLogService::class)->failure(
                DCPLogService::OPERATION_EVENT,
                $model->organization_id,
                $model instanceof DCPOrder ? $model->id : null,
                $model->getMorphClass() . '.' . $event->type->value,
                $exception,
                bulkId: $model instanceof DCPOrder ? $model->dcp_order_bulk_id : $model->id,
            );
        }
    }

    /**
     * @param Dispatcher $events
     * @return void
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(DCPLifecycleEvent::class, [self::class, 'handle']);
    }
}
