<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\BaseJsonResource;
use App\Models\Employee;
use App\Models\Voucher;
use App\Services\EventLogService\Models\EventLog;

/**
 * @property EventLog $resource
 * @property Employee $employee
 */
class EventLogResource extends BaseJsonResource
{
    public const array LOAD = [
        'identity.primary_email',
    ];

    public const array LOAD_MORPH = [
        'loggable' => [
            Voucher::class => ['fund'],
        ],
    ];

    /**
     * Transform the resource into an array.
     *
     * @param $request
     * @return array
     */
    public function toArray($request): array
    {
        $eventLog = $this->resource;
        abort_unless($this->employee instanceof Employee, 403);

        return array_merge($eventLog->only([
            'id', 'event', 'identity_address', 'loggable_id',
        ]), [
            'identity_email' => $eventLog->getIdentityEmail($this->employee),
            'loggable_locale' => $eventLog->loggable_locale_dashboard,
            'event_locale' => $eventLog->eventDescriptionLocaleDashboard($this->employee),
            'note' => $eventLog->getNote(),
        ], $this->timestamps($eventLog, 'created_at'));
    }
}
