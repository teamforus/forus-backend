<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\DCPOrderBulks\IndexDCPOrderBulksRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\DCPOrderBulkResource;
use App\Models\Organization;
use App\Services\DCPService\Models\DCPOrder;
use App\Services\DCPService\Models\DCPOrderBulk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DCPOrderBulksController extends Controller
{
    /**
     * @param IndexDCPOrderBulksRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return AnonymousResourceCollection
     */
    public function index(
        IndexDCPOrderBulksRequest $request,
        Organization $organization,
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAny', [DCPOrderBulk::class, $organization]);

        $query = DCPOrderBulk::query()
            ->where('organization_id', $organization->id)
            ->when($request->input('state'), fn ($builder, $state) => $builder->where('state', $state))
            ->withCount(DCPOrderBulk::orderCounts())
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        return DCPOrderBulkResource::queryCollection($query, $request);
    }

    /**
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return DCPOrderBulkResource|JsonResponse
     */
    public function store(BaseFormRequest $request, Organization $organization): DCPOrderBulkResource|JsonResponse
    {
        $this->authorize('show', $organization);
        $this->authorize('store', [DCPOrderBulk::class, $organization]);

        if (!DCPOrder::isEnabled()) {
            return new JsonResponse(['message' => trans('dcp.errors.disabled')], 403);
        }

        $bulk = DCPOrderBulk::makeBulk($organization, $organization->findEmployee($request->auth_address()));

        if (!$bulk) {
            return new JsonResponse(['message' => trans('dcp.errors.nothing_to_approve')], 422);
        }

        return DCPOrderBulkResource::create($bulk);
    }

    /**
     * @param Organization $organization
     * @param DCPOrderBulk $dcpOrderBulk
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return DCPOrderBulkResource
     */
    public function show(Organization $organization, DCPOrderBulk $dcpOrderBulk): DCPOrderBulkResource
    {
        $this->authorize('show', $organization);
        $this->authorize('show', [$dcpOrderBulk, $organization]);

        return DCPOrderBulkResource::create($dcpOrderBulk);
    }
}
