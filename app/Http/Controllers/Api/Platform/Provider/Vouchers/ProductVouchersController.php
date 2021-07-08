<?php

namespace App\Http\Controllers\Api\Platform\Provider\Vouchers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Provider\Vouchers\ProductsVouchers\IndexProductVouchersRequest;
use App\Http\Resources\Provider\ProviderVoucherResource;
use App\Models\ProductReservation;
use App\Models\VoucherToken;
use App\Scopes\Builders\VoucherQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Class ProductVouchersController
 * @package App\Http\Controllers\Api\Platform\Provider\Vouchers
 */
class ProductVouchersController extends Controller
{
    /**
     * @param IndexProductVouchersRequest $request
     * @param VoucherToken $voucherToken
     * @return AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexProductVouchersRequest $request,
        VoucherToken $voucherToken
    ): AnonymousResourceCollection {
        $this->authorize('useAsProvider', $voucherToken->voucher);

        $productVouchersQuery = VoucherQuery::whereProductVouchersCanBeScannedForFundBy(
            $voucherToken->voucher->product_vouchers()->getQuery(),
            $request->auth_address(),
            $voucherToken->voucher->fund_id
        );

        $productVouchersQuery->where(function(Builder $builder) {
            $builder->whereDoesntHave('transactions');
            $builder->whereDoesntHave('product_reservation', function(Builder $builder) {
                $builder->where('state', '!=', ProductReservation::STATE_PENDING);
                $builder->orWhereDate('expire_at', '<', now());
            });
        });

        return ProviderVoucherResource::collection($productVouchersQuery->paginate(
            $request->input('per_page', 10)
        ));
    }
}
