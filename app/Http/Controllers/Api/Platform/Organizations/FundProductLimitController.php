<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\FundProductLimits\IndexFundProductLimitsRequest;
use App\Http\Requests\Api\Platform\Organizations\FundProductLimits\StoreFundProductLimitsRequest;
use App\Http\Requests\Api\Platform\Organizations\FundProductLimits\UpdateFundProductLimitsRequest;
use App\Http\Resources\FundProductLimitResource;
use App\Http\Responses\NoContentResponse;
use App\Models\FundProductLimit;
use App\Models\Organization;
use App\Scopes\Builders\FundQuery;
use App\Searches\FundProductLimitSearch;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FundProductLimitController extends Controller
{
    /**
     * @param IndexFundProductLimitsRequest $request
     * @param Organization $organization
     * @return AnonymousResourceCollection
     */
    public function index(
        IndexFundProductLimitsRequest $request,
        Organization $organization,
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', [FundProductLimit::class, $organization]);

        $funds = FundQuery::whereIsConfiguredByForus($organization->funds())->get();

        $search = new FundProductLimitSearch($request->only([
            'q', 'fund_id', 'from', 'to', 'state',
        ]), FundProductLimit::query()->whereIn('fund_id', $funds->pluck('id')->toArray()));

        return FundProductLimitResource::queryCollection($search->query(), $request);
    }

    /**
     * @param StoreFundProductLimitsRequest $request
     * @param Organization $organization
     * @return FundProductLimitResource
     */
    public function store(
        StoreFundProductLimitsRequest $request,
        Organization $organization,
    ): FundProductLimitResource {
        $this->authorize('create', [FundProductLimit::class, $organization]);

        $fundProductLimit = FundProductLimit::create($request->only(['fund_id', 'state', 'type', 'limit']));
        $fundProductLimit->updateProducts($request->get('products', []));

        return FundProductLimitResource::create($fundProductLimit);
    }

    /**
     * @param Organization $organization
     * @param FundProductLimit $fundProductLimit
     * @return FundProductLimitResource
     */
    public function show(
        Organization $organization,
        FundProductLimit $fundProductLimit,
    ): FundProductLimitResource {
        $this->authorize('show', [$fundProductLimit, $organization]);

        return FundProductLimitResource::create($fundProductLimit);
    }

    /**
     * @param UpdateFundProductLimitsRequest $request
     * @param Organization $organization
     * @param FundProductLimit $fundProductLimit
     * @return FundProductLimitResource
     */
    public function update(
        UpdateFundProductLimitsRequest $request,
        Organization $organization,
        FundProductLimit $fundProductLimit,
    ): FundProductLimitResource {
        $this->authorize('update', [$fundProductLimit, $organization]);

        $fundProductLimit->update($request->only(['fund_id', 'state', 'type', 'limit']));
        $fundProductLimit->updateProducts($request->get('products', []));

        return FundProductLimitResource::create($fundProductLimit);
    }

    /**
     * @param Organization $organization
     * @param FundProductLimit $fundProductLimit
     * @return NoContentResponse
     */
    public function destroy(
        Organization $organization,
        FundProductLimit $fundProductLimit,
    ): NoContentResponse {
        $this->authorize('destroy', [$fundProductLimit, $organization]);

        $fundProductLimit->delete();

        return new NoContentResponse();
    }
}
