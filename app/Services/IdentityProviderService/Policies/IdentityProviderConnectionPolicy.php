<?php

namespace App\Services\IdentityProviderService\Policies;

use App\Models\Identity;
use App\Models\IdentityProxy;
use App\Models\Organization;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderException;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Services\IdentityProviderAccessService;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class IdentityProviderConnectionPolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @param IdentityProxy $proxy
     * @param ?IdentityProviderConnection $connection
     * @return Response
     */
    public function manage(
        Identity $identity,
        Organization $organization,
        IdentityProxy $proxy,
        ?IdentityProviderConnection $connection = null,
    ): Response {
        if ($connection && $connection->organization_id !== $organization->id) {
            return $this->denyAsNotFound(__('exceptions.not_found.default'));
        }

        if ($proxy->identity_address !== $identity->address) {
            return $this->deny(__('policies.identity_providers.session_identity_mismatch'));
        }

        if (!$organization->isOwner($identity)) {
            return $this->deny(__('policies.identity_providers.owner_required'));
        }

        if ($proxy->identity_provider_binding()->exists()) {
            return $this->deny(__('policies.identity_providers.local_sign_in_required'));
        }

        try {
            resolve(IdentityProviderAccessService::class)->assertSsoAllowed($organization);
        } catch (IdentityProviderException $exception) {
            return $this->deny($exception->getMessage());
        }

        return $this->allow();
    }
}
