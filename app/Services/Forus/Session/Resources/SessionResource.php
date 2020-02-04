<?php

namespace App\Services\Forus\Session\Resources;

use App\Services\Forus\Session\Models\Session;
use App\Services\Forus\Session\Models\SessionRequest;
use App\Services\Forus\Session\Services\Browser;
use App\Services\Forus\Session\Services\GeoIp;
use App\Services\Forus\Session\SessionService;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class SessionResource
 * @property Session $resource
 * @package App\Http\Resources
 */
class SessionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request) {
        $session = $this->resource;
        $sessionStartTime = $session->last_request->created_at;

        $currentSession =  SessionService::currentSession();
        $currentSessionId = $currentSession ? $currentSession->id : null;

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

    private function requestData(SessionRequest $request) {
        if (Browser::isEnabled()) {
            $agentData = Browser::getAgentData($request->user_agent);
            $agentDataArray = $agentData->isDetected() ? $agentData->toArray() : null;
        } else {
            $agentData = null;
            $agentDataArray = null;
        }

        $locationData = GeoIp::isAvailable() ? GeoIp::getLocation($request->ip) : null;

        return [
            'time_passed'       => $request->created_at->diffInSeconds(now()),
            'location'          => $locationData,
            'client_type'       => $request->client_type,
            'client_version'    => $request->client_version,
            'device_available'  => Browser::isEnabled(),
            'device_string'     => $agentData ? $agentData->toString() : null,
            'device'            => $agentDataArray,
        ];
    }
}
