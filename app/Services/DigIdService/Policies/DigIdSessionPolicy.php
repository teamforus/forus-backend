<?php

namespace App\Services\DigIdService\Policies;

use App\Models\Identity;
use App\Models\Implementation;
use Illuminate\Auth\Access\Response;

class DigIdSessionPolicy
{
    /**
     * @param Identity|null $identity
     * @param Implementation|null $implementation
     * @param string|null $clientType
     * @param bool $isAuthRequest
     * @return Response
     */
    public function start(
        ?Identity $identity,
        ?Implementation $implementation,
        ?string $clientType,
        bool $isAuthRequest,
    ): Response {
        if (!$clientType || !in_array($clientType, Implementation::FRONTEND_KEYS, true)) {
            return Response::deny(trans('requests.digid.invalid_client_type'));
        }

        if (!$implementation) {
            return Response::deny(trans('requests.digid.invalid_client_type'));
        }

        if (!$implementation->digidEnabled()) {
            return Response::deny(trans('requests.digid.digid_not_enabled'));
        }

        if (!$isAuthRequest && !$identity?->address) {
            return Response::deny(trans('requests.digid.sign_in_first'));
        }

        return Response::allow();
    }
}
