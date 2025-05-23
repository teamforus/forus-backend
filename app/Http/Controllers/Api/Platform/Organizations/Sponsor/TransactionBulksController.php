<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor;

use App\Exports\VoucherTransactionBulksExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\TransactionBulks\IndexTransactionBulksRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\TransactionBulks\StoreTransactionBulksRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\TransactionBulks\UpdateTransactionBulksRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\Arr\ExportFieldArrResource;
use App\Http\Resources\VoucherTransactionBulkResource;
use App\Models\Organization;
use App\Models\VoucherTransactionBulk;
use App\Scopes\Builders\VoucherTransactionBulkQuery;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class TransactionBulksController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexTransactionBulksRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @noinspection PhpUnused
     */
    public function index(
        IndexTransactionBulksRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAny', [VoucherTransactionBulk::class, $organization]);

        $query = VoucherTransactionBulk::search($request, $organization);

        return VoucherTransactionBulkResource::queryCollection(VoucherTransactionBulkQuery::order(
            $query,
            $request->input('order_by'),
            $request->input('order_dir')
        ), $request);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param VoucherTransactionBulk $voucherTransactionBulk
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return VoucherTransactionBulkResource
     * @noinspection PhpUnused
     */
    public function show(
        Organization $organization,
        VoucherTransactionBulk $voucherTransactionBulk
    ): VoucherTransactionBulkResource {
        $this->authorize('show', $organization);
        $this->authorize('show', [$voucherTransactionBulk, $organization]);

        return VoucherTransactionBulkResource::create($voucherTransactionBulk);
    }

    /**
     * Display the specified resource.
     *
     * @param StoreTransactionBulksRequest $request
     * @param Organization $organization
     * @throws AuthorizationException
     * @return AnonymousResourceCollection
     * @noinspection PhpUnused
     */
    public function store(
        StoreTransactionBulksRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('store', [VoucherTransactionBulk::class, $organization]);

        $employee = $organization->findEmployee($request->auth_address());
        $bulks = VoucherTransactionBulk::buildBulksForOrganization($organization, $employee, $request);
        $transactionBulks = VoucherTransactionBulk::query()->whereIn('id', $bulks);

        return VoucherTransactionBulkResource::queryCollection($transactionBulks);
    }

    /**
     * Display the specified resource.
     *
     * @param UpdateTransactionBulksRequest $request
     * @param Organization $organization
     * @param VoucherTransactionBulk $transactionBulk
     * @throws \Illuminate\Auth\Access\AuthorizationException|Throwable
     * @return VoucherTransactionBulkResource
     * @noinspection PhpUnused
     */
    public function update(
        UpdateTransactionBulksRequest $request,
        Organization $organization,
        VoucherTransactionBulk $transactionBulk
    ): VoucherTransactionBulkResource {
        $this->authorize('show', $organization);
        $this->authorize('resetBulk', [$transactionBulk, $organization]);

        $employee = $request->employee($organization);
        $implementation = $request->implementation();

        if ($transactionBulk->bank_connection->bank->isBunq()) {
            $transactionBulk->resetBulk($employee);
        } else {
            $transactionBulk->submitBulkToBNG($employee, $implementation);
        }

        return VoucherTransactionBulkResource::create($transactionBulk);
    }

    /**
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @param VoucherTransactionBulk $transactionBulk
     * @throws Throwable
     * @return VoucherTransactionBulkResource
     * @noinspection PhpUnused
     */
    public function setAccepted(
        BaseFormRequest $request,
        Organization $organization,
        VoucherTransactionBulk $transactionBulk
    ): VoucherTransactionBulkResource {
        $this->authorize('show', $organization);
        $this->authorize('setAcceptedManually', [$transactionBulk, $organization]);

        $transactionBulk->setAcceptedBNG($request->employee($organization));

        return VoucherTransactionBulkResource::create($transactionBulk);
    }

    /**
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @param VoucherTransactionBulk $transactionBulk
     * @throws AuthorizationException
     * @return string
     * @noinspection PhpUnused
     */
    public function exportSEPA(
        BaseFormRequest $request,
        Organization $organization,
        VoucherTransactionBulk $transactionBulk
    ): string {
        $this->authorize('show', $organization);
        $this->authorize('exportBulkToBNG', [$transactionBulk, $organization]);

        $transactionBulk->setExported($request->employee($organization));

        return $transactionBulk->getBulkPaymentToBNGXML();
    }

    /**
     * @param Organization $organization
     * @throws AuthorizationException
     * @return AnonymousResourceCollection
     * @noinspection PhpUnused
     */
    public function getExportFields(Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('show', $organization);
        $this->authorize('viewAny', [VoucherTransactionBulk::class, $organization]);

        return ExportFieldArrResource::collection(VoucherTransactionBulksExport::getExportFields());
    }

    /**
     * @param IndexTransactionBulksRequest $request
     * @param Organization $organization
     * @throws AuthorizationException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @return BinaryFileResponse
     */
    public function export(
        IndexTransactionBulksRequest $request,
        Organization $organization
    ): BinaryFileResponse {
        $this->authorize('show', $organization);
        $this->authorize('viewAny', [VoucherTransactionBulk::class, $organization]);

        $fields = $request->input('fields', VoucherTransactionBulksExport::getExportFieldsRaw());
        $fileData = new VoucherTransactionBulksExport($request, $organization, $fields);
        $fileName = date('Y-m-d H:i:s') . '.' . $request->input('data_format', 'xls');

        return resolve('excel')->download($fileData, $fileName);
    }
}
