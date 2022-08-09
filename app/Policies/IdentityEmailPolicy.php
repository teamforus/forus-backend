<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\IdentityEmail;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class IdentityEmailPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any identity emails.
     *
     * @param Identity $identity
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewAny(Identity $identity): bool
    {
        return $identity->exists;
    }

    /**
     * Determine whether the user can view identity emails.
     *
     * @param Identity $identity
     * @param IdentityEmail $identityEmail
     * @return bool
     * @noinspection PhpUnused
     */
    public function view(Identity $identity, IdentityEmail $identityEmail): bool
    {
        return $identityEmail->identity_address === $identity->address;
    }

    /**
     * Determine whether the user can create identity emails.
     *
     * @param Identity $identity
     * @return bool
     * @noinspection PhpUnused
     */
    public function create(Identity $identity): bool
    {
        return $identity->exists;
    }

    /**
     * Determine whether the user can set this email as primary.
     *
     * @param Identity $identity
     * @param IdentityEmail $identityEmail
     * @return bool|Response
     * @noinspection PhpUnused
     */
    public function makePrimary(Identity $identity, IdentityEmail $identityEmail): Response|bool
    {
        if ($identityEmail->primary) {
            return $this->deny("Already primary");
        }

        if (!$identityEmail->verified) {
            return $this->deny("Please verify email first.");
        }

        return $identityEmail->identity_address === $identity->address;
    }

    /**
     * Determine whether the identity email verification token can be used.
     *
     * @param Identity|null $identity
     * @param IdentityEmail $identityEmail
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function verifyToken(?Identity $identity, IdentityEmail $identityEmail): Response|bool
    {
        if ($identityEmail->verified) {
            return $this->deny("You already have verified your email.");
        }

        return !$identity || $identity->exists;
    }

    /**
     * Determine whether the user can resend the identity email verification token.
     *
     * @param Identity $identity
     * @param IdentityEmail $identityEmail
     * @return bool|Response
     * @noinspection PhpUnused
     */
    public function resend(Identity $identity, IdentityEmail $identityEmail): Response|bool
    {
        if ($identityEmail->verified) {
            return $this->deny("Email already verified.");
        }

        return $identityEmail->identity_address === $identity->address;
    }

    /**
     * Determine whether the user can delete the identity email.
     *
     * @param Identity $identity
     * @param IdentityEmail $identityEmail
     * @return bool|Response
     * @noinspection PhpUnused
     */
    public function delete(Identity $identity, IdentityEmail $identityEmail): Response|bool
    {
        if ($identityEmail->primary) {
            return $this->deny("Can't delete primary email.");
        }

        return $identityEmail->identity_address === $identity->address;
    }
}
