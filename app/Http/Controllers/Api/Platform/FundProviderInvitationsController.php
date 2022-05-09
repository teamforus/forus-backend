<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Resources\FundProviderInvitationResource;
use App\Models\FundProviderInvitation;
use App\Http\Controllers\Controller;

class FundProviderInvitationsController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param FundProviderInvitation $invitation
     * @return FundProviderInvitationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(FundProviderInvitation $invitation): FundProviderInvitationResource
    {
        $this->authorize('showByToken', $invitation);

        return FundProviderInvitationResource::create($invitation);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param FundProviderInvitation $invitation
     * @return FundProviderInvitationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(FundProviderInvitation $invitation): FundProviderInvitationResource
    {
        $this->authorize('showByToken', $invitation);
        $this->authorize('acceptFundProviderInvitation', $invitation);

        return FundProviderInvitationResource::create($invitation->accept());
    }
}
