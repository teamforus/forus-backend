<?php

namespace App\Modules\VoucherPass\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Modules\DCP\Models\DCPOrder;
use App\Modules\VoucherPass\Http\Requests\VoucherPasses\IndexVoucherPassesRequest;
use App\Modules\VoucherPass\Http\Requests\VoucherPasses\StoreBatchVoucherPassesRequest;
use App\Modules\VoucherPass\Http\Requests\VoucherPasses\UpdateVoucherPassRequest;
use App\Modules\VoucherPass\Http\Resources\VoucherPassResource;
use App\Modules\VoucherPass\Http\Resources\VoucherPassTypeResource;
use App\Modules\VoucherPass\Models\VoucherPass;
use App\Modules\VoucherPass\Searches\VoucherPassSearch;
use App\Modules\VoucherPass\Services\VoucherPassService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Throwable;

class VoucherPassesController extends Controller
{
    /**
     * List voucher passes.
     * @param IndexVoucherPassesRequest $request
     * @param Organization $organization
     * @return AnonymousResourceCollection
     */
    public function index(IndexVoucherPassesRequest $request, Organization $organization): AnonymousResourceCollection
    {
        $search = new VoucherPassSearch($request->safe()->only([
            'q', 'state', 'print_requested',
        ]), $organization->voucher_passes());

        return VoucherPassResource::queryCollection($search->query(), $request)->additional([
            'meta' => ['can_prepare_dcp' => Gate::allows('create', [DCPOrder::class, $organization])],
        ]);
    }

    /**
     * Get voucher pass import configuration.
     * @param Organization $organization
     * @throws AuthorizationException
     * @return JsonResponse
     */
    public function configs(Organization $organization): JsonResponse
    {
        $this->authorize('create', [VoucherPass::class, $organization]);

        return new JsonResponse([
            'data' => [
                'columns' => Config::get('voucher_pass.columns'),
                'types' => VoucherPassTypeResource::createCollection(
                    $organization->voucher_pass_types()->orderBy('name')->get(),
                ),
            ],
        ]);
    }

    /**
     * Preview voucher pass changes.
     * @param StoreBatchVoucherPassesRequest $request
     * @param Organization $organization
     * @throws ValidationException
     * @return JsonResponse
     */
    public function storeBatchValidate(
        StoreBatchVoucherPassesRequest $request,
        Organization $organization,
    ): JsonResponse {
        $service = resolve(VoucherPassService::class);

        return new JsonResponse([
            'data' => $service->previewBatch($organization, $request->validated('rows')),
        ]);
    }

    /**
     * Import voucher passes.
     * @param StoreBatchVoucherPassesRequest $request
     * @param Organization $organization
     * @return JsonResponse
     */
    public function storeBatch(StoreBatchVoucherPassesRequest $request, Organization $organization): JsonResponse
    {
        $employee = $request->employee($organization);
        $service = resolve(VoucherPassService::class);

        return new JsonResponse([
            'data' => $service->importBatch($organization, $request->validated('rows'), $employee),
        ]);
    }

    /**
     * Update a voucher pass.
     * @param UpdateVoucherPassRequest $request
     * @param Organization $organization
     * @param VoucherPass $voucherPass
     * @throws ValidationException
     * @throws ModelNotFoundException
     * @return VoucherPassResource|JsonResponse
     */
    public function update(
        UpdateVoucherPassRequest $request,
        Organization $organization,
        VoucherPass $voucherPass,
    ): VoucherPassResource|JsonResponse {
        $employee = $request->employee($organization);
        $service = resolve(VoucherPassService::class);

        try {
            $pass = $service->update($organization, $voucherPass, $request->validated(), $employee);
        } catch (ValidationException|ModelNotFoundException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            return new JsonResponse([
                'message' => trans('voucher_pass::validation.update_failed'),
                'diagnostic_id' => $service->logFailure(
                    $exception,
                    $organization,
                    $employee,
                    'update',
                    passId: $voucherPass->id,
                ),
            ], 500);
        }

        return VoucherPassResource::create($pass);
    }
}
