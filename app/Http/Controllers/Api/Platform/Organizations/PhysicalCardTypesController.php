<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\PhysicalCardTypes\IndexPhysicalCardTypeRequest;
use App\Http\Requests\Api\Platform\Organizations\PhysicalCardTypes\StorePhysicalCardTypeRequest;
use App\Http\Requests\Api\Platform\Organizations\PhysicalCardTypes\UpdatePhysicalCardTypeRequest;
use App\Http\Resources\Sponsor\SponsorPhysicalCardTypeResource;
use App\Models\Organization;
use App\Models\PhysicalCardType;
use App\Searches\PhysicalCardTypeSearch;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Throwable;

class PhysicalCardTypesController extends Controller
{
    /**
     *  Display a listing of the resource.
     *
     * @param IndexPhysicalCardTypeRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(
        IndexPhysicalCardTypeRequest $request,
        Organization $organization,
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', [PhysicalCardType::class, $organization]);

        $query = (new PhysicalCardTypeSearch($request->only([
            'q', 'order_by', 'order_dir', 'fund_id',
        ]), PhysicalCardType::where('organization_id', $organization->id)));

        return SponsorPhysicalCardTypeResource::queryCollection($query->query(), $request);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StorePhysicalCardTypeRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException|Throwable
     * @return SponsorPhysicalCardTypeResource
     */
    public function store(StorePhysicalCardTypeRequest $request, Organization $organization): SponsorPhysicalCardTypeResource
    {
        $this->authorize('show', $organization);
        $this->authorize('store', [PhysicalCardType::class, $organization]);

        /** @var PhysicalCardType $physicalCardType */
        $physicalCardType = $organization->physical_card_types()->create($request->only([
            'name', 'description', 'code_blocks', 'code_block_size',
        ]));

        $physicalCardType->attachMediaByUid($request->input('media_uid'));

        return SponsorPhysicalCardTypeResource::create($physicalCardType);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param PhysicalCardType $physicalCardType
     * @throws AuthorizationException
     * @return SponsorPhysicalCardTypeResource
     */
    public function show(
        Organization $organization,
        PhysicalCardType $physicalCardType
    ): SponsorPhysicalCardTypeResource {
        $this->authorize('show', $organization);
        $this->authorize('show', [$physicalCardType, $organization]);

        return SponsorPhysicalCardTypeResource::create($physicalCardType);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdatePhysicalCardTypeRequest $request
     * @param Organization $organization
     * @param PhysicalCardType $physicalCardType
     *@throws \Illuminate\Auth\Access\AuthorizationException|Throwable
     * @return SponsorPhysicalCardTypeResource
     */
    public function update(
        UpdatePhysicalCardTypeRequest $request,
        Organization $organization,
        PhysicalCardType $physicalCardType,
    ): SponsorPhysicalCardTypeResource {
        $this->authorize('show', $organization);
        $this->authorize('update', [$physicalCardType, $organization]);

        $physicalCardType->update($request->only([
            'name', 'description',
        ]));

        $physicalCardType->attachMediaByUid($request->input('media_uid'));

        return SponsorPhysicalCardTypeResource::create($physicalCardType);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Organization $organization
     * @param PhysicalCardType $physicalCardType
     * @throws \Illuminate\Auth\Access\AuthorizationException|Exception
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Organization $organization, PhysicalCardType $physicalCardType): JsonResponse
    {
        $this->authorize('show', $organization);
        $this->authorize('destroy', [$physicalCardType, $organization]);

        $physicalCardType->delete();

        return new JsonResponse([]);
    }
}
