<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Funds;

use App\Http\Requests\Api\Platform\Funds\ProviderInvitations\IndexFundProviderInvitationsRequest;
use App\Http\Requests\Api\Platform\Funds\ProviderInvitations\StoreFundProviderInvitationsRequest;
use App\Http\Resources\FundProviderInvitationResource;
use App\Models\Fund;
use App\Models\FundProviderInvitation;
use App\Models\Organization;
use App\Http\Controllers\Controller;

class FundProviderInvitationsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexFundProviderInvitationsRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexFundProviderInvitationsRequest $request,
        Organization $organization,
        Fund $fund
    ) {
        $this->authorize('indexSponsor', [
            FundProviderInvitation::class, $fund, $organization
        ]);

        $providers = $fund->provider_invitations()->getQuery();

        if ($request->has('state')) {
            $providers->where([
                'state' => $request->input('state')
            ]);
        }

        return FundProviderInvitationResource::collection($providers->with(
            FundProviderInvitationResource::$load
        )->paginate($request->input('per_page', null)));
    }

    /**
     * Display the specified resource
     *
     * @param Organization $organization
     * @param Fund $fund
     * @param FundProviderInvitation $invitation
     * @return FundProviderInvitationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        Fund $fund,
        FundProviderInvitation $invitation
    ) {
        $this->authorize('showSponsor', [
            $invitation, $fund, $organization
        ]);

        return new FundProviderInvitationResource($invitation);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreFundProviderInvitationsRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function store(
        StoreFundProviderInvitationsRequest $request,
        Organization $organization,
        Fund $fund
    ) {
        $fromFund = Fund::find($request->input('fund_id'));

        $this->authorize('storeSponsor', [
            FundProviderInvitation::class, $fromFund, $fund, $organization
        ]);

        return FundProviderInvitationResource::collection(
            FundProviderInvitation::inviteFromFundToFund($fromFund, $fund)
        );
    }
}
