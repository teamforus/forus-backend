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
     * @param bool $auth2FAConfirmed
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function viewAny(
        Identity $identity,
        bool $auth2FAConfirmed,
    ): Response|bool {
        if (!$identity->exists()) {
            return false;
        }

        return $this->validate2FAFeatureRestriction($identity, $auth2FAConfirmed);
    }

    /**
     * Determine whether the user can view identity emails.
     *
     * @param Identity $identity
     * @param IdentityEmail $identityEmail
     * @param bool $auth2FAConfirmed
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function view(
        Identity $identity,
        IdentityEmail $identityEmail,
        bool $auth2FAConfirmed = false,
    ): Response|bool {
        if ($identityEmail->identity_address !== $identity->address) {
            return false;
        }

        return $this->validate2FAFeatureRestriction($identity, $auth2FAConfirmed);
    }

    /**
     * Determine whether the user can create identity emails.
     *
     * @param Identity $identity
     * @param bool $auth2FAConfirmed
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function create(
        Identity $identity,
        bool $auth2FAConfirmed = false,
    ): Response|bool {
        if (!$identity->exists()) {
            return false;
        }

        return $this->validate2FAFeatureRestriction($identity, $auth2FAConfirmed);
    }

    /**
     * Determine whether the user can set this email as primary.
     *
     * @param Identity $identity
     * @param IdentityEmail $identityEmail
     * @param bool $auth2FAConfirmed
     * @return bool|Response
     * @noinspection PhpUnused
     */
    public function makePrimary(
        Identity $identity,
        IdentityEmail $identityEmail,
        bool $auth2FAConfirmed = false,
    ): Response|bool {
        if ($identityEmail->primary) {
            return $this->deny(trans('policies.email.already_primary'));
        }

        if (!$identityEmail->verified) {
            return $this->deny(trans('policies.email.not_verified'));
        }

        if ($identityEmail->identity_address !== $identity->address) {
            return false;
        }

        return $this->validate2FAFeatureRestriction($identity, $auth2FAConfirmed);
    }

    /**
     * Determine whether the identity email verification token can be used.
     *
     * @param Identity|null $identity
     * @param IdentityEmail $identityEmail
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function emailTokenRedirect(
        ?Identity $identity,
        IdentityEmail $identityEmail,
    ): Response|bool {
        return $identity === null || $identity->exists() && $identityEmail->exists() && $identityEmail->redirect;
    }

    /**
     * Determine whether the identity email verification token can be used.
     *
     * @param Identity $identity
     * @param IdentityEmail $identityEmail
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function emailTokenVerify(
        Identity $identity,
        IdentityEmail $identityEmail,
    ): Response|bool {
        if ($identityEmail->verified) {
            return $this->deny(trans('policies.email.already_verified'));
        }

        if ($identityEmail->identity_address !== $identity->address) {
            return $this->deny(trans('policies.email.invalid_identity'));
        }

        return $identity->exists();
    }

    /**
     * Determine whether the user can resend the identity email verification token.
     *
     * @param Identity $identity
     * @param IdentityEmail $identityEmail
     * @param bool $auth2FAConfirmed
     * @return bool|Response
     * @noinspection PhpUnused
     */
    public function resend(
        Identity $identity,
        IdentityEmail $identityEmail,
        bool $auth2FAConfirmed = false
    ): Response|bool {
        if ($identityEmail->verified) {
            return $this->deny(trans('policies.email.already_verified'));
        }

        if ($identityEmail->identity_address !== $identity->address) {
            return false;
        }

        return $this->validate2FAFeatureRestriction($identity, $auth2FAConfirmed);
    }

    /**
     * Determine whether the user can delete the identity email.
     *
     * @param Identity $identity
     * @param IdentityEmail $identityEmail
     * @param bool $auth2FAConfirmed
     * @return bool|Response
     * @noinspection PhpUnused
     */
    public function delete(
        Identity $identity,
        IdentityEmail $identityEmail,
        bool $auth2FAConfirmed = false
    ): Response|bool {
        if ($identityEmail->primary) {
            return $this->deny(trans('policies.email.cant_delete_primary_email'));
        }

        if ($identityEmail->identity_address !== $identity->address) {
            return false;
        }

        return $this->validate2FAFeatureRestriction($identity, $auth2FAConfirmed);
    }

    /**
     * @param Identity $identity
     * @param bool $auth2FAConfirmed
     * @return Response|bool
     */
    protected function validate2FAFeatureRestriction(Identity $identity, bool $auth2FAConfirmed = false): Response|bool
    {
        if ($identity->load('funds')->isFeature2FARestricted('emails') && !$auth2FAConfirmed) {
            return $this->deny(trans('policies.email.invalid_2fa'));
        }

        return true;
    }
}
