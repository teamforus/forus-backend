<?php

namespace App\Modules\DCP\Http\Resources;

use App\Http\Resources\BaseJsonResource;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Http\Request;

/**
 * @property-read EventLog $resource
 */
class DCPEventLogResource extends BaseJsonResource
{
    public const array LOAD = ['identity.primary_email'];

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $log = $this->resource;

        return [
            ...$log->only([
                'id', 'event',
            ]),
            'event_locale' => $log->getEventLocale(EventLog::TRANSLATION_DASHBOARD),
            'identity_email' => $log->identity?->email,
            'diagnostic_id' => $log->data['diagnostic_id'] ?? null,
            ...$this->makeTimestamps($log->only([
                'created_at',
            ])),
        ];
    }
}
