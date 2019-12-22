<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Resources\FundProviderInvitationResource;
use App\Models\FundProviderInvitation;
use App\Http\Controllers\Controller;
use App\Models\Organization;

class FundProviderInvitationsController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param FundProviderInvitation $fundProviderInvitation
     * @return FundProviderInvitationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(FundProviderInvitation $fundProviderInvitation)
    {
        $this->authorize('showByToken', $fundProviderInvitation);

        return new FundProviderInvitationResource($fundProviderInvitation);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param FundProviderInvitation $fundProviderInvitation
     * @return FundProviderInvitationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        FundProviderInvitation $fundProviderInvitation
    ) {
        $this->authorize('showByToken', $fundProviderInvitation);
        $this->authorize('acceptFundProviderInvitation', $fundProviderInvitation);

        return new FundProviderInvitationResource($fundProviderInvitation->accept());
    }

    /**
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getInvitations(
        Organization $organization
    ) {
        return FundProviderInvitationResource::collection(
            $organization->fund_provider_invitations()->where(
                'state', '!=', 'accepted'
            )->get()
        );
    }

    /**
     * @param Organization $organization
     * @param FundProviderInvitation $fundProviderInvitation
     * @return FundProviderInvitationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function accept(
        Organization $organization,
        FundProviderInvitation $fundProviderInvitation
    ) {
        $this->authorize('showByToken', $fundProviderInvitation);
        $this->authorize('acceptFundProviderInvitation', $fundProviderInvitation);

        return new FundProviderInvitationResource($fundProviderInvitation->accept());
    }
}
