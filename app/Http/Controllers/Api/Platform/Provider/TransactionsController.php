<?php

namespace App\Http\Controllers\Api\Platform\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Provider\Transactions\IndexTransactionsRequest;
use App\Http\Resources\Provider\ProviderVoucherTransactionEmployeeResource;
use App\Models\VoucherTransaction;
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

        $query = VoucherTransaction::search($request);
        $query->whereHas('employee', static function(Builder $builder) use ($request) {
            $builder->where('identity_address', $request->auth_address());

            if ($request->has('organization_id')) {
                $builder->where('organizations_id', '=', $request->input('organization_id'));
            }
        });

        return ProviderVoucherTransactionEmployeeResource::collection($query->with(
            ProviderVoucherTransactionEmployeeResource::$load
        )->paginate($request->input('per_page')));
    }
}
