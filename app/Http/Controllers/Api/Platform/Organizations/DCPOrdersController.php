<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\DCPOrders\IndexDCPOrdersRequest;
use App\Http\Requests\Api\Platform\Organizations\DCPOrders\SetDCPOrderIdDcpRequest;
use App\Http\Requests\Api\Platform\Organizations\DCPOrders\UpdateDCPOrderRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\DCPOrderResource;
use App\Models\Organization;
use App\Searches\DCPOrderSearch;
use App\Services\DCPService\Models\DCPOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Throwable;

class DCPOrdersController extends Controller
{
    /**
     * @param IndexDCPOrdersRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return AnonymousResourceCollection
     */
    public function index(IndexDCPOrdersRequest $request, Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('show', $organization);
        $this->authorize('viewAny', [DCPOrder::class, $organization]);

        $filters = $request->only(['q', 'state', 'state_group', 'dcp_order_bulk_id', 'awaiting_approval']);
        $builder = DCPOrder::where('organization_id', $organization->id);
        $search = new DCPOrderSearch($filters, $builder);

        return DCPOrderResource::queryCollection(
            $search->query()->orderByDesc('created_at')->orderByDesc('id'),
            $request,
        )->additional($request->boolean('with_counts') ? [
            'meta' => ['counts' => DCPOrderSearch::stateGroupCounts($filters, $builder)],
        ] : []);
    }

    /**
     * @param Organization $organization
     * @param DCPOrder $dcpOrder
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return DCPOrderResource
     */
    public function show(Organization $organization, DCPOrder $dcpOrder): DCPOrderResource
    {
        $this->authorize('show', $organization);
        $this->authorize('show', [$dcpOrder, $organization]);

        return DCPOrderResource::create($dcpOrder);
    }

    /**
     * @param UpdateDCPOrderRequest $request
     * @param Organization $organization
     * @param DCPOrder $dcpOrder
     * @throws \Illuminate\Validation\ValidationException|Throwable
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return DCPOrderResource|JsonResponse
     */
    public function update(
        UpdateDCPOrderRequest $request,
        Organization $organization,
        DCPOrder $dcpOrder,
    ): DCPOrderResource|JsonResponse {
        $this->authorize('show', $organization);
        $this->authorize('update', [$dcpOrder, $organization]);

        $order = $dcpOrder->updatePayload(
            (array) $request->input('delivery_address', []),
            (array) $request->input('tags', []),
            $organization->findEmployee($request->auth_address()),
        );

        if (!$order) {
            return new JsonResponse(['message' => trans('dcp.errors.not_editable')], 422);
        }

        return DCPOrderResource::create($order);
    }

    /**
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @param DCPOrder $dcpOrder
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return DCPOrderResource
     */
    public function resetPayload(
        BaseFormRequest $request,
        Organization $organization,
        DCPOrder $dcpOrder,
    ): DCPOrderResource {
        $this->authorize('show', $organization);
        $this->authorize('resetPayload', [$dcpOrder, $organization]);

        $employee = $organization->findEmployee($request->auth_address());

        return DCPOrderResource::create($dcpOrder->resetPayload($employee));
    }

    /**
     * @param SetDCPOrderIdDcpRequest $request
     * @param Organization $organization
     * @param DCPOrder $dcpOrder
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws Throwable
     * @return DCPOrderResource|JsonResponse
     */
    public function setOrderIdDcp(
        SetDCPOrderIdDcpRequest $request,
        Organization $organization,
        DCPOrder $dcpOrder,
    ): DCPOrderResource|JsonResponse {
        $this->authorize('show', $organization);
        $this->authorize('setOrderIdDcp', [$dcpOrder, $organization]);

        $order = $dcpOrder->setOrderIdDcp(
            $request->input('order_id_dcp'),
            $organization->findEmployee($request->auth_address()),
        );

        if (!$order) {
            return new JsonResponse(['message' => trans('dcp.errors.not_duplicate')], 422);
        }

        return DCPOrderResource::create($order);
    }

    /**
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @param DCPOrder $dcpOrder
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return DCPOrderResource|JsonResponse
     */
    public function retry(
        BaseFormRequest $request,
        Organization $organization,
        DCPOrder $dcpOrder,
    ): DCPOrderResource|JsonResponse {
        $this->authorize('show', $organization);
        $this->authorize('retry', [$dcpOrder, $organization]);

        if (!DCPOrder::isEnabled()) {
            return new JsonResponse(['message' => trans('dcp.errors.disabled')], 403);
        }

        $employee = $organization->findEmployee($request->auth_address());

        return DCPOrderResource::create($dcpOrder->retry($employee));
    }
}
