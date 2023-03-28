<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Requests\Api\Platform\Organizations\FundProviderInvitations\IndexFundProviderInvitationRequest;
use App\Http\Requests\Api\Platform\Organizations\FundProviderInvitations\UpdateFundProviderInvitationRequest;
use App\Models\FundProvider;
use App\Models\FundProviderInvitation;
use App\Models\Organization;
use App\Http\Controllers\Controller;
use App\Http\Resources\FundProviderInvitationResource;
use App\Scopes\Builders\FundQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAnyProvider', [FundProviderInvitation::class, $organization]);

        $builder = $organization->fund_provider_invitations();

        if ($request->has('q') && $q = $request->string('q')) {
            $builder->whereHas('fund', fn (Builder $b) => FundQuery::whereQueryFilter($b, $q));
        }

        if ($request->has('expired') && $request->boolean('expired')) {
            $builder->whereIn('id', FundProvider::queryInvitationsArchived($organization)->select('id'));
        }

        if ($request->has('expired') && !$request->boolean('expired')) {
            $builder->whereIn('id', FundProvider::queryInvitationsActive($organization)->select('id'));
        }

        return FundProviderInvitationResource::queryCollection($builder, $request);
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
        FundProviderInvitation $fundProviderInvitation
    ): FundProviderInvitationResource {
        $this->authorize('show', $organization);
        $this->authorize('showProvider', [$fundProviderInvitation, $organization]);

        return FundProviderInvitationResource::create($fundProviderInvitation);
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
        FundProviderInvitation $fundProviderInvitation
    ): FundProviderInvitationResource {
        $this->authorize('show', $organization);
        $this->authorize('acceptProvider', [$fundProviderInvitation, $organization]);

        return FundProviderInvitationResource::create($fundProviderInvitation->accept());
    }
}
