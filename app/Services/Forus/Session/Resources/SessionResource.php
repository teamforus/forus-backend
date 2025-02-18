<?php

namespace App\Services\Forus\Session\Resources;

use App\Http\Resources\BaseJsonResource;
use App\Services\Forus\Session\Models\Session;
use App\Services\Forus\Session\Models\SessionRequest;
use App\Services\Forus\Session\Services\Browser;
use App\Services\Forus\Session\Services\GeoIp;
use App\Services\Forus\Session\SessionService;
use Illuminate\Http\Request;

/**
 * @property Session $resource
 */
class SessionResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $session = $this->resource;
        $currentSessionId = SessionService::currentSession()?->id;

        return [
            ...$this->resource->only([
                'uid', 'identity_address',
            ]),
            'active' => $session->isActive(),
            'current' => $session->id === $currentSessionId,

            'client_type' => $session->first_request->client_type,
            'client_version' => $session->first_request->client_version,
            'locations' => $session->locations(),

            'first_request' => $this->requestData($session->first_request),
            'last_request' => $this->requestData($session->last_request),
            ...$this->makeTimestamps([
                'created_at' => $session->created_at,
            ]),
        ];
    }

    /**
     * @param SessionRequest $request
     * @return array
     */
    private function requestData(SessionRequest $request): array
    {
        if (Browser::isEnabled()) {
            $agentData = Browser::getAgentData($request->user_agent ?: '');
            $agentDataArray = $agentData->isDetected() ? $agentData->toArray() : null;
        } else {
            $agentData = null;
            $agentDataArray = null;
        }

        $locationData = GeoIp::isAvailable() ? GeoIp::getLocation($request->ip) : null;

        return [
            'device' => $agentDataArray,
            'location' => $locationData,
            'client_type' => $request->client_type,
            'client_version' => $request->client_version,
            'device_string' => $agentData?->toString(),
            'device_available' => Browser::isEnabled(),
            'time_passed' => $request->created_at->diffInSeconds(now()),
            'time_passed_locale' => $request->created_at->diffForHumans(now()),
            ...$this->makeTimestamps([
                'created_at' => $request->created_at,
            ]),
        ];
    }
}
