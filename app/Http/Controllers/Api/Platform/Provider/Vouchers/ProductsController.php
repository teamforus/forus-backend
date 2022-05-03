<?php

namespace App\Http\Controllers\Api\Platform\Provider\Vouchers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Provider\Vouchers\Products\IndexProductsRequest;
use App\Http\Resources\Provider\App\ProviderProductAppResource;
use App\Http\Resources\Provider\ProviderSubsidyProductResource;
use App\Models\FundProviderProduct;
use App\Models\Organization;
use App\Models\Product;
use App\Models\VoucherToken;
use App\Scopes\Builders\FundProviderProductQuery;
use App\Scopes\Builders\ProductQuery;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Class ProductsController
 * @package App\Http\Controllers\Api\Platform\Provider\Vouchers
 */
class ProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexProductsRequest $request
     * @param VoucherToken $voucherToken
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function index(
        IndexProductsRequest $request,
        VoucherToken $voucherToken
    ): AnonymousResourceCollection {
        $this->authorize('viewAnyPublic', Product::class);

        $checkForReservableFlag = $request->input('reservable', false);
        $organizationQuery = Organization::queryByIdentityPermissions($request->auth_address(), 'scan_vouchers');

        if ($request->input('organization_id') !== null) {
            $organizationQuery->where('id', $request->input('organization_id'));
        }

        if ($voucherToken->voucher->fund->isTypeSubsidy()) {
            $query = FundProviderProductQuery::whereAvailableForSubsidyVoucher(
                FundProviderProduct::query(),
                $voucherToken->voucher,
                $organizationQuery->select('id')
            );

            return ProviderSubsidyProductResource::queryCollection($query, $request);
        }

        // Product approved to be bought by target voucher
        return ProviderProductAppResource::queryCollection(ProductQuery::whereAvailableForVoucher(
            Product::query(),
            $voucherToken->voucher,
            $organizationQuery->select('id'),
            $checkForReservableFlag
        ), $request);
    }
}
