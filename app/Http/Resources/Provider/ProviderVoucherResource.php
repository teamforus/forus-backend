<?php

namespace App\Http\Resources\Provider;

use App\Http\Resources\MediaCompactResource;
use App\Http\Resources\MediaResource;
use App\Http\Resources\OrganizationBasicResource;
use App\Models\Organization;
use App\Models\Voucher;
use App\Models\VoucherToken;
use App\Scopes\Builders\OrganizationQuery;
use App\Scopes\Builders\VoucherQuery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class ProviderVoucherResource
 * @property VoucherToken|Voucher $resource
 * @package App\Http\Resources\Provider
 */
class ProviderVoucherResource extends Resource
{
    /**
     * ProviderVoucherResource constructor.
     * @param VoucherToken|Voucher $resource
     */
    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|mixed|void|null
     */
    public function toArray($request)
    {
        if ($this->resource instanceof VoucherToken) {
            $voucherToken = $this->resource;
        } else if ($this->resource instanceof Voucher) {
            $voucherToken = $this->resource->token_without_confirmation;
        } else {
            return null;
        }

        if ($voucherToken->voucher->product) {
            return $this->productVoucher($voucherToken);
        }

        return $this->regularVoucher($request, auth_address(), $voucherToken);
    }

    /**
     * Transform the resource into an array.
     *
     * @param $request
     * @param string $identityAddress
     * @param VoucherToken $voucherToken
     * @return array
     */
    private function regularVoucher(
        $request,
        string $identityAddress,
        VoucherToken $voucherToken
    ) {
        $voucher = $voucherToken->voucher;

        $allowedOrganizations = OrganizationQuery::whereHasPermissionToScanVoucher(
            Organization::query(),
            $identityAddress,
            $voucher
        )->select('id', 'name')->get()->map(function($organization) {
            return collect($organization)->merge([
                'logo' => new MediaCompactResource($organization->logo)
            ]);
        });

        $productVouchers = VoucherQuery::whereProductVouchersCanBeScannedForFundBy(
            $voucher->product_vouchers()->getQuery(),
            $identityAddress,
            $voucher->fund_id
        )->whereDoesntHave('transactions')->get();

        $fundData = collect($voucher->fund)->only([
            'id', 'name', 'state'
        ])->merge([
            'organization'  => new OrganizationBasicResource($voucher->fund->organization),
            'logo'          => new MediaCompactResource($voucher->fund->logo)
        ]);

        return collect($voucher)->only([
            'identity_address', 'fund_id', 'created_at'
        ])->merge([
            'address' => $voucherToken->address,
            'type' => 'regular',
            'amount' => currency_format($voucher->amount_available),
            'fund' => $fundData,
            'allowed_organizations' => $allowedOrganizations,
            // TODO: To be removed in next release
            'allowed_product_categories' => [],
            // TODO: To be removed in next release
            'allowed_products' => [],
            // TODO: To be moved to separate endpoint in next release
            'product_vouchers' => self::collection($productVouchers)->toArray($request),
        ])->toArray();
    }

    /**
     * Transform the resource into an array.
     *
     * @param VoucherToken $voucherToken
     * @return array
     */
    private function productVoucher(
        VoucherToken $voucherToken
    ) {
        $voucher = $voucherToken->voucher;

        return collect($voucher)->only([
            'identity_address', 'fund_id', 'created_at'
        ])->merge([
            'address' => $voucherToken->address,
            'type' => 'product',
            'product' => collect($voucher->product)->only([
                'id', 'name', 'description', 'total_amount', 'sold_amount',
                'product_category_id', 'organization_id'
            ])->merge([
                'price' => currency_format($voucher->product->price),
                'old_price' => currency_format($voucher->product->old_price),
                'photo' => new MediaResource(
                    $voucher->product->photo
                ),
                'organization' => new OrganizationBasicResource(
                    $voucher->product->organization
                ),
            ])->toArray(),
        ])->toArray();
    }
}
