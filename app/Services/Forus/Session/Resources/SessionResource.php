<?php

namespace App\Services\Forus\Session\Resources;

use App\Http\Resources\BaseJsonResource;
use App\Services\Forus\Session\Models\Session;
use App\Services\Forus\Session\Models\SessionRequest;
use App\Services\Forus\Session\Services\Browser;
use App\Services\Forus\Session\Services\GeoIp;
use App\Services\Forus\Session\SessionService;

/**
 * @property Session $resource
 */
class SessionResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request  $request
     *
     * @return (\Illuminate\Support\Collection|array|bool|mixed|null|string)[]
     *
     * @psalm-return array{active: bool, current: bool, started_at: string, started_at_locale: null|string, client_type: null|string, client_version: null|string, locations: \Illuminate\Support\Collection|null, first_request: array, last_request: array,...}
     */
    public function toArray($request): array
    {
        $session = $this->resource;
        $sessionStartTime = $session->last_request->created_at;
        $currentSessionId = SessionService::currentSession()?->id;

        return array_merge($this->resource->only([
            'uid', 'identity_address',
        ]), [
            'active'            => $session->isActive(),
            'current'           => $session->id === $currentSessionId,
            'started_at'        => $sessionStartTime->format('Y-m-d H:i:s'),
            'started_at_locale' => format_date_locale($sessionStartTime),

            'client_type'       => $session->first_request->client_type,
            'client_version'    => $session->first_request->client_version,
            'locations'         => $session->locations(),

            'first_request'     => $this->requestData($session->first_request),
            'last_request'      => $this->requestData($session->last_request),
        ]);
    }

    /**
     * @param SessionRequest $request
     *
     * @return (\App\Services\Forus\Session\Services\Data\LocationData|\Illuminate\Config\Repository|array|int|mixed|null|string)[]
     *
     * @psalm-return array{device: array|null, location: \App\Services\Forus\Session\Services\Data\LocationData|null, client_type: null|string, client_version: null|string, device_string: null|string, device_available: \Illuminate\Config\Repository|mixed, time_passed: int, time_passed_locale: string}
     */
    private function requestData(SessionRequest $request): array
    {
        if (Browser::isEnabled()) {
            $agentData = Browser::getAgentData($request->user_agent);
            $agentDataArray = $agentData->isDetected() ? $agentData->toArray() : null;
        } else {
            $agentData = null;
            $agentDataArray = null;
        }

        $locationData = GeoIp::isAvailable() ? GeoIp::getLocation($request->ip) : null;

        return [
            'device'=> $agentDataArray,
            'location' => $locationData,
            'client_type' => $request->client_type,
            'client_version' => $request->client_version,
            'device_string' => $agentData?->toString(),
            'device_available'=> Browser::isEnabled(),
            'time_passed' => $request->created_at->diffInSeconds(now()),
            'time_passed_locale' => $request->created_at->diffForHumans(now()),
        ];
    }
}
