<?php

namespace App\Http\Controllers\Api\Platform\Provider\Vouchers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Provider\Vouchers\Products\IndexProductsRequest;
use App\Http\Resources\Provider\App\ProviderAppProductResource;
use App\Models\Organization;
use App\Models\Product;
use App\Models\VoucherToken;
use App\Scopes\Builders\ProductQuery;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexProductsRequest $request
     * @param VoucherToken $voucherToken
     * @throws AuthorizationException
     * @return AnonymousResourceCollection
     */
    public function index(
        IndexProductsRequest $request,
        VoucherToken $voucherToken
    ): AnonymousResourceCollection {
        $this->authorize('viewAnyPublic', Product::class);

        $voucher = $voucherToken->voucher;
        $checkForReservableFlag = $request->get('reservable', false);
        $organizationQuery = Organization::queryByIdentityPermissions($request->auth_address(), 'scan_vouchers');

        if ($request->get('organization_id') !== null) {
            $organizationQuery->where('id', $request->input('organization_id'));
        }

        $query = ProductQuery::whereAvailableForVoucher(
            Product::query(),
            $voucherToken->voucher,
            $organizationQuery->select('id'),
            $checkForReservableFlag,
        );

        // Product approved to be bought by target voucher
        return ProviderAppProductResource::queryCollection($query, $request, [
            'voucher' => $voucher,
            'reservable' => !!$checkForReservableFlag,
        ]);
    }
}
