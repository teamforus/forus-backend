<?php

namespace App\Http\Resources\Provider\App;

use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\MediaCompactResource;
use App\Http\Resources\OrganizationBasicResource;
use App\Http\Resources\Provider\ProviderSubsidyProductResource;
use App\Models\Fund;
use App\Models\Organization;
use App\Models\Voucher;
use App\Models\VoucherToken;
use App\Scopes\Builders\FundProviderProductQuery;
use App\Scopes\Builders\OrganizationQuery;
use App\Scopes\Builders\ProductQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * @property VoucherToken|Voucher $resource
 */
class ProviderVoucherResource extends BaseJsonResource
{
    protected BaseFormRequest $request;

    /**
     * @param string|null $append
     * @return array
     */
    public static function load(?string $append = null): array
    {
        return array_merge(
            parent::load($append),
            ProviderProductAppResource::load('product')
        );
    }

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|null
     */
    public function toArray($request): ?array
    {
        $this->request = BaseFormRequest::createFrom($request);

        if (!$this->getVoucherToken()) {
            return null;
        }

        $voucherToken = $this->getVoucherToken();
        $useAsProvider = Gate::allows('useAsProvider', $voucherToken->voucher);
        $identityAddress = $this->request->auth_address();

        if ($voucherToken->voucher->isProductType()) {
            return $useAsProvider ? $this->productVoucher($voucherToken, $identityAddress) : null;
        }

        return $this->regularVoucher($identityAddress, $voucherToken, $useAsProvider);
    }

    /**
     * Transform the resource into an array.
     *
     * @param string $identityAddress
     * @param VoucherToken $voucherToken
     * @param bool $useAsProvider
     * @return array
     */
    protected function regularVoucher(
        string $identityAddress,
        VoucherToken $voucherToken,
        bool $useAsProvider = false
    ): array {
        $voucher = $voucherToken->voucher;
        $fund = $voucher->fund;

        return array_merge($voucher->only('identity_address', 'fund_id'), [
            'type' => 'regular',
            'fund' => $this->fundDetails($fund),
            'address' => $voucherToken->address,
            'allowed_organizations' => $this->getAllowedOrganizations($voucher, $identityAddress),
            'allowed_product_organizations' => $this->getAllowedProductOrganizations($voucher, $identityAddress),
        ], $useAsProvider ? [
            'amount' => currency_format($fund->isTypeBudget() ? $voucher->amount_available : 0),
            'amount_locale' => currency_format_locale($fund->isTypeBudget() ? $voucher->amount_available : 0),
        ] : [], $this->timestamps($this, 'created_at'));
    }

    /**
     * Transform the resource into an array.
     *
     * @param VoucherToken $voucherToken
     * @param string $identityAddress
     * @return array
     */
    protected function productVoucher(VoucherToken $voucherToken, string $identityAddress): array
    {
        $voucher = $voucherToken->voucher;

        if ($voucher->fund->isTypeSubsidy()) {
            $productData = $voucher->product->getFundProviderProduct($voucher->fund);
            $productData = ProviderSubsidyProductResource::create($productData)->toArray($this->request);
        } else {
            $productData = ProviderProductAppResource::create($voucher->product);
        }

        return array_merge($voucher->only('identity_address', 'fund_id'), [
            'type' => 'product',
            'address' => $voucherToken->address,
            'fund' => $this->fundDetails($voucher->fund),
            'allowed_organizations' => $this->getAllowedOrganizations($voucher, $identityAddress),
            'product' => $productData,
        ], $this->timestamps($this, 'created_at'));
    }

    /**
     * @param Fund $fund
     * @return array
     */
    protected function fundDetails(Fund $fund): array
    {
        return array_merge($fund->only('id', 'name', 'state', 'type'), [
            'organization'  => (new OrganizationBasicResource($fund->organization))->toArray($this->request),
            'logo'          => (new MediaCompactResource($fund->logo))->toArray($this->request),
        ]);
    }

    /**
     * @param Voucher $voucher
     * @param string|null $identityAddress
     * @return Collection
     */
    protected function getAllowedOrganizations(Voucher $voucher, ?string $identityAddress): Collection
    {
        $builder = Organization::where(static function(Builder $builder) use ($voucher, $identityAddress) {
            OrganizationQuery::whereHasPermissionToScanVoucher($builder, $identityAddress, $voucher);

            if ($voucher->product_id) {
                $builder->where('organizations.id', $voucher->product->organization_id);
            }
        });

        return $this->organizationsQueryToList($builder);
    }

    /**
     * @param Voucher $voucher
     * @param string|null $identityAddress
     * @return Collection
     */
    protected function getAllowedProductOrganizations(Voucher $voucher, ?string $identityAddress): Collection
    {
        $builder = Organization::where(static function(Builder $builder) use ($voucher, $identityAddress) {
            // Has products available for purchase
            $builder->where(function(Builder $builder) use ($voucher, $identityAddress) {
                OrganizationQuery::whereHasPermissions($builder, $identityAddress, 'scan_vouchers');

                if ($voucher->fund->isTypeSubsidy()) {
                    $builder->whereHas('fund_providers', function(Builder $builder) use ($voucher) {
                        $builder->whereHas('fund_provider_products', function(Builder $builder) use ($voucher) {
                            FundProviderProductQuery::whereAvailableForSubsidyVoucher($builder, $voucher);
                        });
                    });
                } else {
                    // Product approved to be bought by target voucher
                    $builder->whereHas('products', function(Builder $builder) use ($voucher) {
                        ProductQuery::whereAvailableForVoucher($builder, $voucher, null, false);
                    });
                }
            });

            foreach ($voucher->product_vouchers as $product_voucher) {
                // Or where at least one of the product vouchers is available for usage
                $builder->orWhere(function(Builder $builder) use ($identityAddress, $product_voucher) {
                    OrganizationQuery::whereHasPermissionToScanVoucher($builder, $identityAddress, $product_voucher);
                });
            }
        });

        return $this->organizationsQueryToList($builder);
    }

    /**
     * @param Builder $builder
     * @return Collection
     */
    protected function organizationsQueryToList(Builder $builder): Collection
    {
        $organizations = $builder->orderBy('name')->get();

        return $organizations->map(function(Organization $organization) {
            return array_merge($organization->only('id', 'name'), [
                'logo' => (new MediaCompactResource($organization->logo))->toArray($this->request),
            ]);
        });
    }

    /**
     * @return VoucherToken|null
     */
    protected function getVoucherToken(): ?VoucherToken
    {
        if ($this->resource instanceof VoucherToken) {
            return $this->resource;
        } else if ($this->resource instanceof Voucher) {
            return $this->resource->token_without_confirmation;
        }

        return null;
    }
}
