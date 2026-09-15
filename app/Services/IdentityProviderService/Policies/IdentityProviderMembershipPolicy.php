<?php

namespace App\Services\IdentityProviderService\Policies;

use App\Models\Identity;
use App\Models\IdentityProxy;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Models\IdentityProviderMembership;
use App\Services\IdentityProviderService\Models\IdentityProviderOidcSession;
use App\Services\IdentityProviderService\Queries\IdentityProviderConnectionQuery;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Config;

class IdentityProviderMembershipPolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @param IdentityProxy $proxy
     * @return Response
     */
    public function manageLinks(Identity $identity, IdentityProxy $proxy): Response
    {
        if ($proxy->identity_address !== $identity->address) {
            return $this->deny(__('policies.identity_providers.session_identity_mismatch'));
        }

        if (!Config::get('identity_providers.enabled')) {
            return $this->deny(__('policies.identity_providers.link_management_disabled'));
        }

        if ($proxy->identity_provider_binding()->exists()) {
            return $this->deny(__('policies.identity_providers.link_local_sign_in_required'));
        }

        return $this->allow();
    }

    /**
     * @param Identity $identity
     * @param IdentityProxy $proxy
     * @param IdentityProviderConnection $connection
     * @return Response
     */
    public function link(
        Identity $identity,
        IdentityProxy $proxy,
        IdentityProviderConnection $connection,
    ): Response {
        $response = $this->manageLinks($identity, $proxy);

        if ($response->denied()) {
            return $response;
        }

        return IdentityProviderConnectionQuery::whereAvailableForSelfLink(
            IdentityProviderConnection::whereKey($connection->id),
            $identity,
        )->exists() ? $this->allow() : $this->deny(__('policies.identity_providers.connection_unavailable'));
    }

    /**
     * @param Identity $identity
     * @param IdentityProviderMembership $link
     * @param IdentityProxy $proxy
     * @return Response
     */
    public function unlink(Identity $identity, IdentityProviderMembership $link, IdentityProxy $proxy): Response
    {
        if ($link->identity_id !== $identity->id) {
            return $this->denyAsNotFound(__('exceptions.not_found.default'));
        }

        return $this->manageLinks($identity, $proxy);
    }

    /**
     * @param Identity $identity
     * @param IdentityProxy $proxy
     * @param IdentityProviderOidcSession $session
     * @return Response
     */
    public function completeLink(Identity $identity, IdentityProxy $proxy, IdentityProviderOidcSession $session): Response
    {
        if ($session->identity_id !== $identity->id || $session->mode !== IdentityProviderOidcSession::MODE_SELF_LINK) {
            return $this->denyAsNotFound(__('exceptions.not_found.default'));
        }

        return $this->manageLinks($identity, $proxy);
    }
}
