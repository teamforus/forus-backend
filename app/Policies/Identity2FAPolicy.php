<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Identity2FA;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class Identity2FAPolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @return bool
     */
    public function state(Identity $identity): bool
    {
        return $identity->exists();
    }

    /**
     * @param Identity $identity
     * @return bool
     */
    public function update(Identity $identity): bool
    {
        return $identity->exists();
    }

    /**
     * @param Identity $identity
     * @param string $type
     * @return Response|bool
     */
    public function store(Identity $identity, string $type): Response|bool
    {
        if ($identity->auth_2fa_providers_active()->where('type', $type)->exists()) {
            return $this->deny('You already have a connection of the same type.');
        }

        return true;
    }

    /**
     * @param Identity $identity
     * @param Identity2FA $identity2FA
     * @return Response|bool
     */
    public function resend(Identity $identity, Identity2FA $identity2FA): Response|bool
    {
        $phoneUsed = $identity2FA->isTypePhone() && Identity2FA::query()->where([
            'state' => Identity2FA::STATE_ACTIVE,
            'phone' => $identity2FA->phone,
        ])->where('uuid', '!=', $identity2FA->uuid)->exists();

        if ($phoneUsed) {
            return $this->deny('Phone number already used.');
        }

        if (!$identity2FA->isTypePhone()) {
            return $this->deny('Invalid provider type.');
        }

        return $identity->address == $identity2FA->identity_address;
    }

    /**
     * @param Identity $identity
     * @param Identity2FA $identity2FA
     * @return Response|bool
     */
    public function activate(Identity $identity, Identity2FA $identity2FA): Response|bool
    {
        $sameTypeExists = $identity->auth_2fa_providers_active()->where([
            'type' => $identity2FA->auth_2fa_provider->type
        ])->exists();

        $phoneUsed = $identity2FA->isTypePhone() && Identity2FA::query()->where([
            'state' => Identity2FA::STATE_ACTIVE,
            'phone' => $identity2FA->phone,
        ])->where('uuid', '!=', $identity2FA->uuid)->exists();

        if ($phoneUsed) {
            return $this->deny('Phone number already used.');
        }

        if ($sameTypeExists) {
            return $this->deny('You already have a connection of the same type.');
        }

        return $identity2FA->isPending() && $identity->address == $identity2FA->identity_address;
    }

    /**
     * @param Identity $identity
     * @param Identity2FA $identity2FA
     * @return Response|bool
     */
    public function authenticate(Identity $identity, Identity2FA $identity2FA): Response|bool
    {
        if (!$identity2FA->isActive()) {
            return $this->deny('Connection is not active.');
        }

        return $identity->address == $identity2FA->identity_address;
    }

    /**
     * @param Identity $identity
     * @param Identity2FA $identity2FA
     * @return Response|bool
     */
    public function deactivate(Identity $identity, Identity2FA $identity2FA): Response|bool
    {
        if (!$identity2FA->isActive()) {
            return $this->deny('Connection is not active.');
        }

        return $identity->address == $identity2FA->identity_address;
    }
}
