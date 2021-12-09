<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Provider;

use App\Events\Funds\FundProviderApplied;
use App\Http\Requests\Api\Platform\Organizations\Provider\StoreFundProviderRequest;
use App\Http\Requests\Api\Platform\Organizations\Provider\UpdateFundProviderRequest;
use App\Http\Resources\FundResource;
use App\Http\Resources\FundProviderResource;
use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Organization;
use App\Http\Controllers\Controller;
use App\Models\FundProvider;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\Api\Platform\Funds\IndexFundsRequest;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class FundProviderController
 * @package App\Http\Controllers\Api\Platform\Organizations\Provider
 */
class FundProviderController extends Controller
{
    /**
     * Display list funds available for apply as provider
     *
     * @param IndexFundsRequest $request
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function availableFunds(
        IndexFundsRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAnyProvider', [FundProvider::class, $organization]);

        $fundsQuery = Implementation::queryFundsByState([
            Fund::STATE_ACTIVE, Fund::STATE_PAUSED
        ])->where('type', '!=', Fund::TYPE_EXTERNAL)->whereNotIn(
            'id', $organization->fund_providers()->pluck('fund_id')
        );

        $meta = [
            'organizations' => Organization::whereHas('funds', static function(
                Builder $builder
            ) use ($fundsQuery) {
                $builder->whereIn('id', $fundsQuery->pluck('id'));
            })->select(['id', 'name'])->get()->map(static function(Organization $organization) {
                return $organization->only('id', 'name');
            }),
            'tags' => Tag::whereHas('funds', static function(Builder $builder) use ($fundsQuery) {
                return $builder->whereIn('funds.id', $fundsQuery->pluck('id'));
            })->select(['key', 'name'])->get()->map(static function(Tag $tag) {
                return $tag->only('key', 'name');
            }),
        ];

        return FundResource::collection(Fund::search($request->only([
            'tag', 'organization_id', 'fund_id', 'q', 'implementation_id', 'order_by', 'order_by_dir'
        ]), $fundsQuery)->latest()->paginate(
            $request->input('per_page', 10))
        )->additional(compact('meta'));
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        Request $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAnyProvider', [FundProvider::class, $organization]);

        $state = $request->input('state', false);
        $fund_providers = $organization->fund_providers();

        if ($state) {
            $fund_providers->where('state', $state);
        }

        return FundProviderResource::collection($fund_providers->with(
            FundProviderResource::$load
        )->get());
    }

    /**
     * Apply as provider to fund
     *
     * @param StoreFundProviderRequest $request
     * @param Organization $organization
     * @return FundProviderResource
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function store(
        StoreFundProviderRequest $request,
        Organization $organization
    ): FundProviderResource {
        $this->authorize('show', $organization);
        $this->authorize('storeProvider', [FundProvider::class, $organization]);

        $fund_id = $request->only('fund_id');

        if (Fund::find($fund_id)->is_external) {
            abort(403, 'provider_apply_no_permission');
        }

        /** @var FundProvider $fundProvider */
        $fundProvider = $organization->fund_providers()->firstOrCreate($fund_id);

        FundProviderApplied::dispatch($fundProvider->fund, $fundProvider);

        return new FundProviderResource($fundProvider);
    }

    /**
     * Display the specified resource
     *
     * @param Organization $organization
     * @param FundProvider $organizationFund
     * @return FundProviderResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        FundProvider $organizationFund
    ): FundProviderResource {
        $this->authorize('show', $organization);
        $this->authorize('showProvider', [$organizationFund, $organization]);

        return new FundProviderResource($organizationFund);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateFundProviderRequest $request
     * @param Organization $organization
     * @param FundProvider $organizationFund
     * @return FundProviderResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        UpdateFundProviderRequest $request,
        Organization $organization,
        FundProvider $organizationFund
    ): FundProviderResource {
        $this->authorize('show', $organization);
        $this->authorize('updateProvider', [$organizationFund, $organization]);

        $organizationFund->update($request->only([
            'state'
        ]));

        return new FundProviderResource($organizationFund);
    }

    /**
     * Delete the specified resource
     *
     * @param Organization $organization
     * @param FundProvider $organizationFund
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function destroy(
        Organization $organization,
        FundProvider $organizationFund
    ): JsonResponse {
        $this->authorize('show', $organization);
        $this->authorize('deleteProvider', [$organizationFund, $organization]);

        $organizationFund->delete();

        return response()->json([]);
    }
}
