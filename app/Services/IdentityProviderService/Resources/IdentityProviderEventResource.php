<?php

namespace App\Services\IdentityProviderService\Resources;

use App\Http\Resources\BaseJsonResource;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;

/**
 * @property-read EventLog $resource
 */
class IdentityProviderEventResource extends BaseJsonResource
{
    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $log = $this->resource;
        $errorCode = $log->data['error_code'] ?? null;

        return [
            'id' => $log->id,
            'event_type' => $log->event,
            'event_type_locale' => Lang::get("identity_provider.events.$log->event"),
            'outcome' => $log->data['outcome'],
            'error_code' => $errorCode,
            'error_code_locale' => $errorCode ? Lang::get("identity_provider.errors.$errorCode") : null,
            ...$this->makeTimestamps($log->only(['created_at'])),
        ];
    }
}
