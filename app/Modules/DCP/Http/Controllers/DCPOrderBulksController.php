<?php

namespace App\Modules\DCP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Modules\DCP\Http\Requests\DCPEventLogs\IndexDCPEventLogsRequest;
use App\Modules\DCP\Http\Requests\DCPOrderBulks\IndexDCPOrderBulksRequest;
use App\Modules\DCP\Http\Requests\DCPOrderBulks\StoreDCPOrderBulkRequest;
use App\Modules\DCP\Http\Resources\DCPEventLogResource;
use App\Modules\DCP\Http\Resources\DCPOrderBulkResource;
use App\Modules\DCP\Models\DCPOrderBulk;
use App\Modules\DCP\Searches\DCPOrderBulkSearch;
use App\Modules\DCP\Services\DCPOrderService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Throwable;

class DCPOrderBulksController extends Controller
{
    /**
     * List DCP order bulks.
     * @param IndexDCPOrderBulksRequest $request
     * @param Organization $organization
     * @return AnonymousResourceCollection
     */
    public function index(IndexDCPOrderBulksRequest $request, Organization $organization): AnonymousResourceCollection
    {
        $filters = $request->safe()->only([
            'q',
        ]);

        $query = DCPOrderBulk::query()->where('organization_id', $organization->id);
        $search = new DCPOrderBulkSearch($filters, $query);

        return DCPOrderBulkResource::queryCollection(
            $search->query()->withCount(DCPOrderBulk::orderCounts())->latest('id'),
            $request,
        );
    }

    /**
     * Approve DCP orders as a bulk.
     * @param StoreDCPOrderBulkRequest $request
     * @param Organization $organization
     * @throws Throwable
     * @return DCPOrderBulkResource
     */
    public function store(StoreDCPOrderBulkRequest $request, Organization $organization): DCPOrderBulkResource
    {
        $service = resolve(DCPOrderService::class);
        $bulk = $service->approveBulk($organization, $request->employee($organization));

        return DCPOrderBulkResource::create($bulk->loadCount(DCPOrderBulk::orderCounts()));
    }

    /**
     * Get DCP order bulk details.
     * @param Organization $organization
     * @param DCPOrderBulk $dcpOrderBulk
     * @throws AuthorizationException
     * @return DCPOrderBulkResource
     */
    public function show(Organization $organization, DCPOrderBulk $dcpOrderBulk): DCPOrderBulkResource
    {
        $this->authorize('show', [$dcpOrderBulk, $organization]);

        return DCPOrderBulkResource::create($dcpOrderBulk->loadCount(DCPOrderBulk::orderCounts()));
    }

    /**
     * Count orders ready for bulk approval.
     * @param Organization $organization
     * @throws AuthorizationException
     * @throws Throwable
     * @return JsonResponse
     */
    public function readyCount(Organization $organization): JsonResponse
    {
        $this->authorize('store', [DCPOrderBulk::class, $organization]);

        return new JsonResponse([
            'data' => ['count' => resolve(DCPOrderService::class)->readyOrderCount($organization)],
        ]);
    }

    /**
     * List DCP bulk history.
     * @param IndexDCPEventLogsRequest $request
     * @param Organization $organization
     * @param DCPOrderBulk $dcpOrderBulk
     * @throws AuthorizationException
     * @return AnonymousResourceCollection
     */
    public function logs(
        IndexDCPEventLogsRequest $request,
        Organization $organization,
        DCPOrderBulk $dcpOrderBulk,
    ): AnonymousResourceCollection {
        $this->authorize('show', [$dcpOrderBulk, $organization]);

        return DCPEventLogResource::queryCollection($dcpOrderBulk->logs()->latest('id'), $request);
    }
}
