<?php

namespace App\Services\Forus\Session\Policies;

use App\Models\Identity;
use App\Services\Forus\Session\Models\Session;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class SessionPolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @param bool $auth2FAConfirmed
     *
     * @return Response|true
     */
    protected function validate2FAFeatureRestriction(
        Identity $identity,
        bool $auth2FAConfirmed = false,
    ): bool|Response|bool {
        if ($identity->load('funds')->isFeature2FARestricted('sessions') && !$auth2FAConfirmed) {
            return $this->deny('Invalid 2FA state.');
        }

        return true;
    }
}
