<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\BaseJsonResource;
use App\Models\Voucher;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Support\Arr;

/**
 * @property EventLog $resource
 */
class EventLogResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param $request
     * @return array
     */
    public function toArray($request): array
    {
        $eventLog = $this->resource;

        return array_merge($eventLog->only([
            'id', 'event', 'identity_address', 'loggable_id',
        ]), [
            'identity_email' => $eventLog->identity?->email,
            'loggable_locale' => $eventLog->loggable_locale_dashboard,
            'event_locale' => $eventLog->event_locale_dashboard,
            'note' => $this->getNote($eventLog),
        ], $this->timestamps($eventLog, 'created_at'));
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