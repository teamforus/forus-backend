<?php

namespace App\Policies;

use App\Services\Forus\Identity\Models\IdentityEmail;
use Illuminate\Auth\Access\HandlesAuthorization;

class IdentityEmailPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any identity emails.
     * 
     * @param string|null $identity_address
     * @return bool
     */
    public function viewAny(
        ?string $identity_address
    ) {
        return !empty($identity_address);
    }

    /**
     * Determine whether the user can view identity emails.
     *
     * @param string|null $identity_address
     * @param IdentityEmail $identityEmail
     * @return bool
     */
    public function view(
        ?string $identity_address,
        IdentityEmail $identityEmail
    ) {
        return $identityEmail->identity_address === $identity_address;
    }

    /**
     * Determine whether the user can create identity emails.
     *
     * @param string|null $identity_address
     * @return bool
     */
    public function create(?string $identity_address)
    {
        return !empty($identity_address);
    }

    /**
     * Determine whether the user can update the identity email.
     *
     * @param string|null $identity_address
     * @param  \App\Services\Forus\Identity\Models\IdentityEmail  $identityEmail
     * @return bool
     */
    public function update(?string $identity_address, IdentityEmail $identityEmail)
    {
        return $identityEmail->identity_address === $identity_address;
    }

    /**
     * Determine whether the user can set this email as primary.
     *
     * @param string|null $identity_address
     * @param IdentityEmail $identityEmail
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function makePrimary(?string $identity_address, IdentityEmail $identityEmail)
    {
        if ($identityEmail->primary) {
            return $this->deny("Already primary");
        }

        if (!$identityEmail->verified) {
            return $this->deny("Please verify email first.");
        }

        return $identityEmail->identity_address === $identity_address;
    }

    /**
     * Determine whether the identity email verification token can be used.
     *
     * @param string|null $identity_address
     * @param IdentityEmail $identityEmail
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function verifyToken(?string $identity_address, IdentityEmail $identityEmail)
    {
        if ($identityEmail->verified) {
            return $this->deny("You already have verified your email.");
        }

        return isset($identity_address);
    }

    /**
     * Determine whether the user can resend the identity email verification token.
     *
     * @param string|null $identity_address
     * @param IdentityEmail $identityEmail
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function resend(?string $identity_address, IdentityEmail $identityEmail)
    {
        if ($identityEmail->verified) {
            return $this->deny("Email already verified.");
        }

        return $identityEmail->identity_address === $identity_address;
    }

    /**
     * Determine whether the user can delete the identity email.
     *
     * @param string|null $identity_address
     * @param IdentityEmail $identityEmail
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function delete(?string $identity_address, IdentityEmail $identityEmail)
    {
        if ($identityEmail->primary) {
            return $this->deny("Can't delete primary email.");
        }

        return $identityEmail->identity_address === $identity_address;
    }

    /**
     * Determine whether the user can restore the identity email.
     *
     * @param string|null $identity_address
     * @param  \App\Services\Forus\Identity\Models\IdentityEmail  $identityEmail
     * @return bool
     */
    public function restore(?string $identity_address, IdentityEmail $identityEmail)
    {
        return $identityEmail->identity_address === $identity_address;
    }

    /**
     * Determine whether the user can permanently delete the identity email.
     *
     * @param string|null $identity_address
     * @param IdentityEmail $identityEmail
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function forceDelete(?string $identity_address, IdentityEmail $identityEmail)
    {
        if ($identityEmail->primary) {
            return $this->deny("Can't delete primary email.");
        }

        return $identityEmail->identity_address === $identity_address;
    }
}
