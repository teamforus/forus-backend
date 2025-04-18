<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Resources\FundProviderInvitationResource;
use App\Models\FundProviderInvitation;

class FundProviderInvitationsController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param FundProviderInvitation $invitation
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return FundProviderInvitationResource
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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return FundProviderInvitationResource
     */
    public function update(FundProviderInvitation $invitation): FundProviderInvitationResource
    {
        $this->authorize('showByToken', $invitation);
        $this->authorize('acceptFundProviderInvitation', $invitation);

        return FundProviderInvitationResource::create($invitation->accept());
    }
}
