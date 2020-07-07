<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Requests\Api\Platform\Organizations\ExternalFunds\IndexExternalFundsRequest;
use App\Http\Requests\Api\Platform\Organizations\ExternalFunds\UpdateExternalFundRequest;
use App\Scopes\Builders\FundCriteriaValidatorQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewExternalFunds', [$organization]);

        /** @var LengthAwarePaginator $funds */
        $funds = FundQuery::whereExternalValidatorFilter(
            Fund::query(), $organization->id
        )->paginate($request->input('per_page', 10));

        $funds->getCollection()->transform(static function(Fund $fund) use ($organization) {
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
    ): ExternalFundResource {
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

            if (!($criterion['accepted'] ?? false)) {
                foreach ($organization->employees as $employee) {
                    foreach ($criterionModel->fund->fund_requests as $fund_request) {
                        $fund_request->resignEmployee($employee, $criterionModel);
                    }
                }
            }
        }

        $fund->setAttribute('external_validator', $organization);

        return new ExternalFundResource($fund);
    }
}
