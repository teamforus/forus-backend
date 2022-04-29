<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor;

use App\Http\Requests\Api\Platform\Organizations\Sponsor\TransactionBulks\IndexTransactionBulksRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\TransactionBulks\UpdateTransactionBulksRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\VoucherTransactionBulkResource;
use App\Models\Organization;
use App\Http\Controllers\Controller;
use App\Models\VoucherTransactionBulk;
use App\Scopes\Builders\VoucherTransactionBulkQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Throwable;

/**
 * Class TransactionsController
 * @package App\Http\Controllers\Api\Platform\Organizations\Sponsor
 * @noinspection PhpUnused
 */
class TransactionBulksController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexTransactionBulksRequest $request
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function index(
        IndexTransactionBulksRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAny', [VoucherTransactionBulk::class, $organization]);

        $query = VoucherTransactionBulk::query();

        $query = $query->whereHas('bank_connection', function (Builder $builder) use ($organization) {
            $builder->where('bank_connections.organization_id', $organization->id);
        });

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
     * @return VoucherTransactionBulkResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
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
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @return AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function store(
        BaseFormRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('store', [VoucherTransactionBulk::class, $organization]);

        $employee = $organization->findEmployee($request->auth_address());
        $bulks = VoucherTransactionBulk::buildBulksForOrganization($organization, $employee);
        $transactionBulks = VoucherTransactionBulk::query()->whereIn('id', $bulks);

        return VoucherTransactionBulkResource::queryCollection($transactionBulks);
    }

    /**
     * Display the specified resource.
     *
     * @param UpdateTransactionBulksRequest $request
     * @param Organization $organization
     * @param VoucherTransactionBulk $transactionBulk
     * @return VoucherTransactionBulkResource
     * @throws \Illuminate\Auth\Access\AuthorizationException|Throwable
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
}
