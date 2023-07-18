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
     * @return Response|bool
     */
    public function viewAny(Identity $identity, bool $auth2FAConfirmed = false): Response|bool
    {
        if (!$identity->exists()) {
            return false;
        }

        return $this->validate2FAFeatureRestriction($identity, $auth2FAConfirmed);
    }

    /**
     * @param Identity $identity
     * @param Session $session
     * @param bool $auth2FAConfirmed
     * @return Response|bool
     */
    public function show(
        Identity $identity,
        Session $session,
        bool $auth2FAConfirmed = false,
    ): Response|bool {
        if ($session->identity_address === $identity->address) {
            return false;
        }

        return $this->validate2FAFeatureRestriction($identity, $auth2FAConfirmed);
    }

    /**
     * @param Identity $identity
     * @param Session $session
     * @param bool $auth2FAConfirmed
     * @return Response|bool
     */
    public function terminate(
        Identity $identity,
        Session $session,
        bool $auth2FAConfirmed = false
    ): Response|bool {
        if ($session->identity_address === $identity->address) {
            return false;
        }

        return $this->validate2FAFeatureRestriction($identity, $auth2FAConfirmed);
    }

    /**
     * @param Identity $identity
     * @param bool $auth2FAConfirmed
     * @return Response|bool
     */
    public function terminateAll(Identity $identity, bool $auth2FAConfirmed = false): Response|bool
    {
        if (!$identity->exists()) {
            return false;
        }

        return $this->validate2FAFeatureRestriction($identity, $auth2FAConfirmed);
    }

    /**
     * @param Identity $identity
     * @param bool $auth2FAConfirmed
     * @return Response|bool
     */
    protected function validate2FAFeatureRestriction(
        Identity $identity,
        bool $auth2FAConfirmed = false,
    ): Response|bool {
        if ($identity->load('funds')->isFeature2FARestricted('sessions') && !$auth2FAConfirmed) {
            return $this->deny('Invalid 2FA state.');
        }

        return true;
    }
}
