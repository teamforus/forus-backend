<?php

namespace App\Http\Controllers\Api\Platform\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Provider\Transactions\IndexTransactionsRequest;
use App\Http\Resources\Provider\ProviderVoucherTransactionEmployeeResource;
use App\Models\VoucherTransaction;
use App\Scopes\Builders\VoucherTransactionQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Class TransactionsController
 * @package App\Http\Controllers\Api\Platform\Provider
 */
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

        $query = VoucherTransaction::search($request);
        $query->whereHas('employee', static function(Builder $builder) use ($request) {
            $builder->where(array_merge($request->only([
                'organization_id'
            ]), [
                'identity_address' => $request->auth_address()
            ]));
        });

        return ProviderVoucherTransactionEmployeeResource::queryCollection(VoucherTransactionQuery::order(
            $query,
            $request->input('order_by'),
            $request->input('order_dir')
        ));
    }
}
