<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Requests\Api\Platform\Organizations\ExternalFunds\IndexExternalFundsRequest;
use App\Http\Requests\Api\Platform\Organizations\ExternalFunds\UpdateExternalFundRequest;
use App\Scopes\Builders\FundCriteriaValidatorQuery;
use \Illuminate\Pagination\LengthAwarePaginator;
use App\Http\Resources\ExternalFundResource;
use App\Models\Fund;
use App\Models\FundCriterion;
use App\Models\Organization;
use App\Http\Controllers\Controller;
use App\Scopes\Builders\FundQuery;

/**
 * Class FundsController
 * @package App\Http\Controllers\Api\Platform\Organizations
 */
class ExternalFundsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexExternalFundsRequest $request
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexExternalFundsRequest $request,
        Organization $organization
    ) {
        $this->authorize('show', $organization);
        $this->authorize('viewExternalFunds', [$organization]);

        /** @var LengthAwarePaginator $funds */
        $funds = FundQuery::whereExternalValidatorFilter(
            Fund::query(),
            $organization->id
        )->paginate($request->input('per_page', 10));

        $funds->getCollection()->transform(function(Fund $fund) use ($organization) {
            $fund->setAttribute("external_validator", $organization);
            return $fund;
        });

        return ExternalFundResource::collection($funds);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateExternalFundRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @return ExternalFundResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        UpdateExternalFundRequest $request,
        Organization $organization,
        Fund $fund
    ) {
        $this->authorize('update', $organization);
        $this->authorize('updateExternalFunds', [$organization, $fund]);

        foreach ($request->input('criteria', []) as $criterion) {
            $criterionModel = FundCriterion::find($criterion['id']);

            FundCriteriaValidatorQuery::whereHasExternalValidatorFilter(
                $criterionModel->fund_criterion_validators()->getQuery(),
                $organization->id
            )->update([
                'accepted' => $criterion['accepted'] ?? false
            ]);
        }

        $fund->setAttribute('external_validator', $organization);

        return new ExternalFundResource($fund);
    }
}
