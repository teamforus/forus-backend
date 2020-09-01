<?php

namespace App\Http\Controllers\Api\Platform\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Provider\Transactions\IndexTransactionsRequest;
use App\Http\Resources\Provider\ProviderVoucherTransactionEmployeeResource;
use App\Models\VoucherTransaction;
use App\Scopes\Builders\OrganizationQuery;
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

        $query = VoucherTransaction::search($request)->whereHas('employee', static function(
            Builder $builder
        ) use ($request) {
            $builder->where('identity_address', auth_address());

            if ($request->has('organization_id')) {
                $builder->where('organization_id', '=', $request->input('organization_id'));
            }

            $builder->whereHas('organization', static function(Builder $builder) {
                return OrganizationQuery::whereHasPermissions($builder, auth_address(), [
                    'scan_vouchers'
                ]);
            });
        });

        return ProviderVoucherTransactionEmployeeResource::collection($query->with(
            ProviderVoucherTransactionEmployeeResource::$load
        )->paginate($request->input('per_page')));
    }
}
