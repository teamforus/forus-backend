<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Funds;

use App\Http\Requests\Api\Platform\Organizations\Funds\Validators\StoreFundValidatorRule;
use App\Http\Requests\Api\Platform\Organizations\Funds\Validators\UpdateFundValidatorRule;
use App\Http\Resources\FundValidatorResource;
use App\Models\Fund;
use App\Models\FundValidator;
use App\Models\Organization;
use App\Http\Controllers\Controller;

class FundValidatorsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Organization $organization
     * @param Fund $fund
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        Organization $organization,
        Fund $fund
    ) {
        $this->authorize('show', $organization);
        $this->authorize('show', $fund);
        $this->authorize('index', FundValidator::class);

        return FundValidatorResource::collection($fund->validators);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreFundValidatorRule $request
     * @param Organization $organization
     * @param Fund $fund
     * @return FundValidatorResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        StoreFundValidatorRule $request,
        Organization $organization,
        Fund $fund
    ) {
        $this->authorize('update', $organization);
        $this->authorize('update', $fund);
        $this->authorize('store', FundValidator::class);

        return new FundValidatorResource($fund->validators()->create(
            $request->only([
                'identity_address'
            ])
        ));
    }

    /**
     * Display the specified resource
     *
     * @param Organization $organization
     * @param Fund $fund
     * @param FundValidator $fundValidator
     * @return FundValidatorResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        Fund $fund,
        FundValidator $fundValidator
    ) {
        $this->authorize('show', $organization);
        $this->authorize('show', $fund);
        $this->authorize('show', $fundValidator);

        return new FundValidatorResource($fundValidator);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateFundValidatorRule $request
     * @param Organization $organization
     * @param Fund $fund
     * @param FundValidator $fundValidator
     * @return FundValidatorResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        UpdateFundValidatorRule $request,
        Organization $organization,
        Fund $fund,
        FundValidator $fundValidator
    ) {
        $this->authorize('update', $organization);
        $this->authorize('update', $fund);
        $this->authorize('update', $fundValidator);

        $fundValidator->update($request->only([
            'identity_address'
        ]));

        return new FundValidatorResource($fundValidator);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Organization $organization
     * @param Fund $fund
     * @param FundValidator $fundValidator
     * @return array
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function destroy(
        Organization $organization,
        Fund $fund,
        FundValidator $fundValidator
    ) {
        $this->authorize('update', $organization);
        $this->authorize('update', $fund);
        $this->authorize('destroy', $fundValidator);

        $fundValidator->delete();

        return [];
    }
}
