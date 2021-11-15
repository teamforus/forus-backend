<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor;

use App\Http\Requests\Api\Platform\Organizations\Sponsor\TransactionBulks\IndexTransactionBulksController;
use App\Http\Resources\Sponsor\SponsorVoucherTransactionResource;
use App\Http\Resources\VoucherTransactionBulkResource;
use App\Models\Organization;
use App\Models\VoucherTransaction;
use App\Http\Controllers\Controller;
use App\Models\VoucherTransactionBulk;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
     * @param IndexTransactionBulksController $request
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function index(
        IndexTransactionBulksController $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAny', [VoucherTransaction::class, $organization]);

        $query = VoucherTransactionBulk::query();

        $query = $query->whereHas('bank_connection', function (Builder $builder) use ($organization) {
            $builder->where('bank_connections.organization_id', $organization->id);
        });

        return VoucherTransactionBulkResource::collection($query->with(
            VoucherTransactionBulkResource::load()
        )->paginate($request->input('per_page')));
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

        return new VoucherTransactionBulkResource($voucherTransactionBulk->load(
            VoucherTransactionBulkResource::load()
        ));
    }
}
