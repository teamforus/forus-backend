<?php

namespace App\Modules\VoucherPass\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Modules\DCP\Models\DCPOrder;
use App\Modules\VoucherPass\Http\Requests\VoucherPassDCP\PrepareVoucherPassDCPRequest;
use App\Modules\VoucherPass\Services\VoucherPassDCPService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;

class VoucherPassDCPController extends Controller
{
    /**
     * List pass IDs for print preparation.
     * @param Organization $organization
     * @throws AuthorizationException
     * @return JsonResponse
     */
    public function ids(Organization $organization): JsonResponse
    {
        $this->authorize('create', [DCPOrder::class, $organization]);

        return new JsonResponse(['data' => resolve(VoucherPassDCPService::class)->candidateIds($organization)]);
    }

    /**
     * Preview print order changes.
     * @param PrepareVoucherPassDCPRequest $request
     * @param Organization $organization
     * @return JsonResponse
     */
    public function preview(PrepareVoucherPassDCPRequest $request, Organization $organization): JsonResponse
    {
        $service = resolve(VoucherPassDCPService::class);

        return new JsonResponse(['data' => $service->prepareBatch(
            $organization,
            $request->validated('pass_ids'),
            $request->employee($organization),
            preview: true,
        )]);
    }

    /**
     * Synchronize pass printing details to draft orders.
     * @param PrepareVoucherPassDCPRequest $request
     * @param Organization $organization
     * @return JsonResponse
     */
    public function sync(PrepareVoucherPassDCPRequest $request, Organization $organization): JsonResponse
    {
        $service = resolve(VoucherPassDCPService::class);

        return new JsonResponse(['data' => $service->prepareBatch(
            $organization,
            $request->validated('pass_ids'),
            $request->employee($organization),
        )]);
    }
}
