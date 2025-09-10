<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\FundPhysicalCardTypes\IndexFundPhysicalCardTypeRequest;
use App\Http\Requests\Api\Platform\Organizations\FundPhysicalCardTypes\StoreFundPhysicalCardTypeRequest;
use App\Http\Requests\Api\Platform\Organizations\FundPhysicalCardTypes\UpdateFundPhysicalCardTypeRequest;
use App\Http\Resources\Sponsor\SponsorFundPhysicalCardTypeResource;
use App\Models\FundPhysicalCardType;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FundPhysicalCardTypesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexFundPhysicalCardTypeRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(
        IndexFundPhysicalCardTypeRequest $request,
        Organization $organization,
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAny', [FundPhysicalCardType::class, $organization]);

        $query = FundPhysicalCardType::query()
            ->whereRelation('fund', 'organization_id', $organization->id);

        if ($request->has('q')) {
            $query->where(function ($query) use ($request) {
                $query->whereHas('fund', function ($query) use ($request) {
                    $query->where('name', 'like', "%{$request->validated('q')}%");
                });

                $query->orWhereHas('physical_card_type', function ($query) use ($request) {
                    $query->where('name', 'like', "%{$request->validated('q')}%");
                    $query->orWhere('description', 'like', "%{$request->validated('q')}%");
                });
            });
        }

        if ($request->has('fund_id')) {
            $query->where('fund_id', $request->validated('fund_id'));
        }

        if ($request->has('physical_card_type_id')) {
            $query->where('physical_card_type_id', $request->validated('physical_card_type_id'));
        }

        return SponsorFundPhysicalCardTypeResource::queryCollection($query, $request);
    }

    /**
     * @param StoreFundPhysicalCardTypeRequest $request
     * @param Organization $organization
     * @return SponsorFundPhysicalCardTypeResource
     */
    public function store(
        StoreFundPhysicalCardTypeRequest $request,
        Organization $organization,
    ): SponsorFundPhysicalCardTypeResource {
        $this->authorize('show', $organization);
        $this->authorize('store', [FundPhysicalCardType::class, $organization]);

        $fund = $organization->funds()->findOrFail($request->post('fund_id'));

        return SponsorFundPhysicalCardTypeResource::create($fund->fund_physical_card_types()->updateOrCreate(
            $request->only([
                'physical_card_type_id',
            ]),
            $request->only([
                'allow_physical_card_linking', 'allow_physical_card_requests', 'allow_physical_card_deactivation',
            ]),
        ));
    }

    /**
     * @param UpdateFundPhysicalCardTypeRequest $request
     * @param Organization $organization
     * @param FundPhysicalCardType $fundPhysicalCardType
     * @return SponsorFundPhysicalCardTypeResource
     */
    public function update(
        UpdateFundPhysicalCardTypeRequest $request,
        Organization $organization,
        FundPhysicalCardType $fundPhysicalCardType,
    ): SponsorFundPhysicalCardTypeResource {
        $this->authorize('show', $organization);
        $this->authorize('update', [$fundPhysicalCardType, $organization]);

        $fundPhysicalCardType->update($request->only([
            'allow_physical_card_linking', 'allow_physical_card_requests', 'allow_physical_card_deactivation',
        ]));

        return SponsorFundPhysicalCardTypeResource::create($fundPhysicalCardType);
    }

    /**
     * @param Organization $organization
     * @param FundPhysicalCardType $fundPhysicalCardType
     * @return JsonResponse
     */
    public function destroy(
        Organization $organization,
        FundPhysicalCardType $fundPhysicalCardType,
    ) {
        $this->authorize('show', $organization);
        $this->authorize('delete', [$fundPhysicalCardType, $organization]);

        $fundPhysicalCardType->delete();

        return new JsonResponse(null);
    }
}
