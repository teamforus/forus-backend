<?php

namespace App\Services\IdentityProviderService\Policies;

use App\Models\Identity;
use App\Models\IdentityProxy;
use App\Models\Organization;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderException;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Models\IdentityProviderMembership;
use App\Services\IdentityProviderService\Models\IdentityProviderScimCredential;
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

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @param IdentityProxy $proxy
     * @param IdentityProviderConnection $connection
     * @param string $accountType
     * @return Response
     */
    public function issueScimCredential(
        Identity $identity,
        Organization $organization,
        IdentityProxy $proxy,
        IdentityProviderConnection $connection,
        string $accountType,
    ): Response {
        return $this->manageProvisioning($identity, $organization, $proxy, $connection, $accountType);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @param IdentityProxy $proxy
     * @param IdentityProviderConnection $connection
     * @param IdentityProviderScimCredential $credential
     * @return Response
     */
    public function revokeScimCredential(
        Identity $identity,
        Organization $organization,
        IdentityProxy $proxy,
        IdentityProviderConnection $connection,
        IdentityProviderScimCredential $credential,
    ): Response {
        if ($credential->connection_id !== $connection->id) {
            return $this->denyAsNotFound(__('exceptions.not_found.default'));
        }

        return $this->manageProvisioning($identity, $organization, $proxy, $connection, $credential->account_type);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @param IdentityProxy $proxy
     * @param IdentityProviderConnection $connection
     * @param string $accountType
     * @return Response
     */
    protected function manageProvisioning(
        Identity $identity,
        Organization $organization,
        IdentityProxy $proxy,
        IdentityProviderConnection $connection,
        string $accountType,
    ): Response {
        $response = $this->manage($identity, $organization, $proxy, $connection);

        if ($response->denied()) {
            return $response;
        }

        if ($connection->isDisconnected()) {
            return $this->deny(__('exceptions.identity_providers.scim_connection_disconnected'));
        }

        return match ($accountType) {
            IdentityProviderMembership::ACCOUNT_TYPE_EMPLOYEE =>
                $organization->allow_identity_provider_employee_provisioning
                    ? $this->allow()
                    : $this->deny(__('policies.identity_providers.employee_provisioning_disabled')),
            IdentityProviderMembership::ACCOUNT_TYPE_REQUESTER =>
                $organization->allow_identity_provider_requester_provisioning
                    ? $this->allow()
                    : $this->deny(__('policies.identity_providers.requester_provisioning_disabled')),
            default => $this->deny(__('policies.misc.unauthorized_action')),
        };
    }
}
