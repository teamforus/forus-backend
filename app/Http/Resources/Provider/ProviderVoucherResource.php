<?php

namespace App\Http\Resources\Provider;

use App\Http\Resources\MediaCompactResource;
use App\Http\Resources\MediaResource;
use App\Http\Resources\OrganizationBasicResource;
use App\Models\Fund;
use App\Models\Organization;
use App\Models\Voucher;
use App\Models\VoucherToken;
use App\Scopes\Builders\OrganizationQuery;
use App\Scopes\Builders\VoucherQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Support\Collection;

/**
 * Class ProviderVoucherResource
 * @property VoucherToken|Voucher $resource
 * @package App\Http\Resources\Provider
 */
class ProviderVoucherResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request|any $request
     * @return array|mixed|void|null
     */
    public function toArray($request): ?array
    {
        $auth_address = auth_address();

        if ($this->resource instanceof VoucherToken) {
            $voucherToken = $this->resource;
        } else if ($this->resource instanceof Voucher) {
            $voucherToken = $this->resource->token_without_confirmation;
        } else {
            return null;
        }

        if ($voucherToken->voucher->isProductType()) {
            return $this->productVoucher($voucherToken, $auth_address);
        }

        return $this->regularVoucher($request, $auth_address, $voucherToken);
    }

    /**
     * @param Voucher $voucher
     * @param string|null $identityAddress
     * @return Collection
     */
    private function getAllowedOrganizations(Voucher $voucher, ?string $identityAddress): Collection {
        return OrganizationQuery::whereHasPermissionToScanVoucher(
            Organization::query(), $identityAddress, $voucher
        )->select([
            'id', 'name'
        ])->where(static function(Builder $builder) use ($voucher) {
            if ($voucher->product_id) {
                $builder->where('organizations.id', $voucher->product->organization_id);
            }
        })->get()->map(static function($organization) {
            return collect($organization)->merge([
                'logo' => new MediaCompactResource($organization->logo)
            ]);
        });
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
    ): array {
        $voucher = $voucherToken->voucher;
        $fund = $voucher->fund;

        $productVouchers = VoucherQuery::whereProductVouchersCanBeScannedForFundBy(
            $voucher->product_vouchers()->getQuery(),
            $identityAddress,
            $voucher->fund_id
        )->whereDoesntHave('transactions')->get();

        return collect($voucher)->only([
            'identity_address', 'fund_id', 'created_at'
        ])->merge([
            'address' => $voucherToken->address,
            'type' => 'regular',
            'amount' => currency_format($fund->isTypeBudget() ? $voucher->amount_available : 0),
            'fund' => $this->fundDetails($fund),
            'allowed_organizations' => $this->getAllowedOrganizations($voucher, $identityAddress),
        ])->merge(env('DISABLE_DEPRECATED_API') ? [] : [
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
     * @param string $identityAddress
     * @return array
     */
    private function productVoucher(
        VoucherToken $voucherToken,
        string $identityAddress
    ): array {
        $voucher = $voucherToken->voucher;

        return collect($voucher)->only([
            'identity_address', 'fund_id',
        ])->merge([
            'created_at' => $voucher->created_at_string,
            'address' => $voucherToken->address,
            'type' => 'product',
            'fund' => $this->fundDetails($voucher->fund),
            'allowed_organizations' => $this->getAllowedOrganizations($voucher, $identityAddress),
            'product' => collect($voucher->product)->only([
                'id', 'name', 'description', 'total_amount', 'sold_amount',
                'product_category_id', 'organization_id'
            ])->merge([
                'price' => currency_format($voucher->product->price),
                'old_price' => currency_format($voucher->product->old_price),
                'photo' => new MediaResource($voucher->product->photo),
                'organization' => new OrganizationBasicResource($voucher->product->organization),
            ])->toArray(),
        ])->toArray();
    }

    /**
     * @param Fund $fund
     * @return array
     */
    private function fundDetails(
        Fund $fund
    ): array{
        return array_merge($fund->only([
            'id', 'name', 'state', 'type',
        ]), [
            'organization'  => new OrganizationBasicResource($fund->organization),
            'logo'          => new MediaCompactResource($fund->logo)
        ]);
    }
}
