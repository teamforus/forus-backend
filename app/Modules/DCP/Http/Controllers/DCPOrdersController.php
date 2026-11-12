<?php

namespace App\Modules\DCP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Modules\DCP\Http\Requests\DCPEventLogs\IndexDCPEventLogsRequest;
use App\Modules\DCP\Http\Requests\DCPOrders\IndexDCPOrdersRequest;
use App\Modules\DCP\Http\Resources\DCPEventLogResource;
use App\Modules\DCP\Http\Resources\DCPOrderResource;
use App\Modules\DCP\Models\DCPOrder;
use App\Modules\DCP\Models\DCPOrderBulk;
use App\Modules\DCP\Searches\DCPOrderSearch;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class DCPOrdersController extends Controller
{
    /**
     * List DCP orders.
     * @param IndexDCPOrdersRequest $request
     * @param Organization $organization
     * @return AnonymousResourceCollection
     */
    public function index(IndexDCPOrdersRequest $request, Organization $organization): AnonymousResourceCollection
    {
        $filters = $request->safe()->only([
            'q', 'state', 'state_group', 'dcp_order_bulk_id',
        ]);

        $query = DCPOrder::query()->where('organization_id', $organization->id);
        $search = new DCPOrderSearch($filters, $query);
        $resource = DCPOrderResource::queryCollection($search->query()->latest('id'), $request);

        return $resource->additional([
            'meta' => [
                'can_approve' => Gate::allows('store', [DCPOrderBulk::class, $organization]),
                ...($request->boolean('with_counts') ? [
                    'counts' => DCPOrderSearch::stateGroupCounts($filters, $query),
                ] : []),
            ],
        ]);
    }

    /**
     * Get DCP order details.
     * @param Organization $organization
     * @param DCPOrder $dcpOrder
     * @throws AuthorizationException
     * @return DCPOrderResource
     */
    public function show(Organization $organization, DCPOrder $dcpOrder): DCPOrderResource
    {
        $this->authorize('show', [$dcpOrder, $organization]);

        return DCPOrderResource::create($dcpOrder);
    }

    /**
     * List DCP order history.
     * @param IndexDCPEventLogsRequest $request
     * @param Organization $organization
     * @param DCPOrder $dcpOrder
     * @throws AuthorizationException
     * @return AnonymousResourceCollection
     */
    public function logs(
        IndexDCPEventLogsRequest $request,
        Organization $organization,
        DCPOrder $dcpOrder,
    ): AnonymousResourceCollection {
        $this->authorize('show', [$dcpOrder, $organization]);

        return DCPEventLogResource::queryCollection($dcpOrder->logs()->latest('id'), $request);
    }
}
