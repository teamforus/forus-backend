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
}
