<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\BaseJsonResource;
use App\Models\Employee;
use App\Models\Voucher;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Support\Arr;

/**
 * @property EventLog $resource
 * @property Employee $employee
 */
class EventLogResource extends BaseJsonResource
{
    public const LOAD = [
        'identity.primary_email',
    ];

    public const LOAD_MORPH = [
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
            'identity_email' => $this->identityEmail($eventLog, $this->employee),
            'loggable_locale' => $eventLog->loggable_locale_dashboard,
            'event_locale' => $eventLog->eventDescriptionLocaleDashboard($this->employee),
            'note' => $this->getNote($eventLog),
        ], $this->timestamps($eventLog, 'created_at'));
    }

    /**
     * @param EventLog $eventLog
     * @param Employee $employee
     * @return string|null
     */
    protected function identityEmail(EventLog $eventLog, Employee $employee): ?string
    {
        return $eventLog->isSameOrganization($employee) ? $eventLog->identity?->email : null;
    }

    /**
     * @param EventLog $eventLog
     * @return string|null
     */
    public function getNote(EventLog $eventLog): ?string
    {
        if ($eventLog->loggable_type === (new Voucher())->getMorphClass()) {
            $isTransaction = $eventLog->event == 'transaction';
            $initiator = Arr::get($eventLog->data, 'voucher_transaction_initiator', 'provider');
            $initiatorIsSponsor = $initiator == 'sponsor';

            $notePattern = $isTransaction && $initiatorIsSponsor ? 'voucher_transaction_%s' : '%s';

            return Arr::get($eventLog->data, sprintf($notePattern, 'note'));
        }

        return null;
    }
}