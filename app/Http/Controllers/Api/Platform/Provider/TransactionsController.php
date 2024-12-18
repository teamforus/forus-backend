<?php

namespace App\Http\Controllers\Api\Platform\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Provider\Transactions\IndexTransactionsRequest;
use App\Http\Resources\Provider\ProviderVoucherTransactionEmployeeResource;
use App\Models\VoucherTransaction;
use App\Scopes\Builders\VoucherTransactionQuery;
use App\Searches\VoucherTransactionsSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransactionsController extends Controller
{
    /**
     * @param IndexTransactionsRequest $request
     * @return AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(IndexTransactionsRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', VoucherTransaction::class);

        $query = new VoucherTransactionsSearch($request->only([
            'q', 'targets', 'state', 'from', 'to', 'amount_min', 'amount_max',
            'transfer_in_min', 'transfer_in_max', 'fund_state',
        ]), VoucherTransaction::query());
        $query = $query->query();

        $query->whereHas('employee', fn(Builder $builder) => $builder->where(array_merge([
            'identity_address' => $request->auth_address(),
        ], $request->input('organization_id') ? [
            'organization_id' => $request->input('organization_id'),
        ]: [])))->where('initiator', VoucherTransaction::INITIATOR_PROVIDER);

        return ProviderVoucherTransactionEmployeeResource::queryCollection(VoucherTransactionQuery::order(
            $query,
            $request->input('order_by'),
            $request->input('order_dir')
        ));
    }
}
