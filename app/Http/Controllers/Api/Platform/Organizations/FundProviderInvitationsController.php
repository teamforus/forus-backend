<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Requests\Api\Platform\Organizations\FundProviderInvitations\IndexFundProviderInvitationRequest;
use App\Http\Requests\Api\Platform\Organizations\FundProviderInvitations\UpdateFundProviderInvitationRequest;
use App\Models\FundProviderInvitation;
use App\Models\Organization;
use App\Http\Controllers\Controller;

use App\Http\Resources\FundProviderInvitationResource;

class FundProviderInvitationsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexFundProviderInvitationRequest $request
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexFundProviderInvitationRequest $request,
        Organization $organization
    ) {
        $this->authorize('show', $organization);
        $this->authorize('indexProvider', [FundProviderInvitation::class, $organization]);

        return FundProviderInvitationResource::collection(
            $organization->fund_provider_invitations()->paginate(
                $request->input('per_page', 10)
            )
        );
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param FundProviderInvitation $fundProviderInvitation
     * @return FundProviderInvitationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        FundProviderInvitation $fundProviderInvitation)
    {
        $this->authorize('show', $organization);
        $this->authorize('showProvider', [
            $fundProviderInvitation, $organization
        ]);

        return new FundProviderInvitationResource(
            $fundProviderInvitation
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateFundProviderInvitationRequest $request
     * @param Organization $organization
     * @param FundProviderInvitation $fundProviderInvitation
     * @return FundProviderInvitationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        UpdateFundProviderInvitationRequest $request,
        Organization $organization,
        FundProviderInvitation $fundProviderInvitation)
    {
        $this->authorize('show', $organization);
        $this->authorize('acceptProvider', [
            $fundProviderInvitation, $organization
        ]);

        return new FundProviderInvitationResource($fundProviderInvitation->accept());
    }
}
