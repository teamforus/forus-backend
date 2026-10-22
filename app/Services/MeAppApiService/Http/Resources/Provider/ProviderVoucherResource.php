<?php

namespace App\Services\MeAppApiService\Http\Resources\Provider;

use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\BaseJsonResource;
use App\Models\Fund;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Voucher;
use App\Models\VoucherToken;
use App\Scopes\Builders\OrganizationQuery;
use App\Scopes\Builders\ProductQuery;
use App\Services\MeAppApiService\Http\Resources\Shared\MediaResource;
use App\Services\MeAppApiService\Http\Resources\Shared\OrganizationResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * @property-read Voucher $resource
 */
class ProviderVoucherResource extends BaseJsonResource
{
    public const array LOAD = [
        'token_without_confirmation',
        'fund.fund_config',
        'product_vouchers',
        'product_reservation.product',
    ];

    public const array LOAD_NESTED = [
        'fund.logo' => MediaResource::class,
        'fund.organization' => OrganizationResource::class,
        'product' => ProviderProductResource::class,
    ];

    protected BaseFormRequest $request;

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|null
     */
    public function toArray(Request $request): ?array
    {
        $this->request = BaseFormRequest::createFrom($request);

        if (!$voucherToken = $this->getVoucherToken()) {
            return null;
        }

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
     * @scramble-return array{
     *   fund_id: int, type: 'regular', address: string,
     *   fund: array{
     *     id: int, name: string, state: string, type: string|null,
     *     organization: OrganizationResource, logo: MediaResource|null
     *   },
     *   allowed_organizations: list<array{id: int, name: string, logo: MediaResource|null}>,
     *   allowed_product_organizations: list<array{id: int, name: string, logo: MediaResource|null}>,
     *   amount?: string, amount_locale?: string, amount_visible?: bool|null,
     *   created_at: string|null, created_at_locale: string|null
     * }
     */
    protected function regularVoucher(
        string $identityAddress,
        VoucherToken $voucherToken,
        bool $useAsProvider = false
    ): array {
        $voucher = $voucherToken->voucher;
        $fund = $voucher->fund;

        return [
            ...$voucher->only([
                'fund_id',
            ]),
            'type' => 'regular',
            'fund' => $this->fundDetails($fund),
            'address' => $voucherToken->address,
            'allowed_organizations' => $this->getAllowedOrganizations($voucher, $identityAddress),
            'allowed_product_organizations' => $this->getAllowedProductOrganizations($voucher, $identityAddress),
            ...$useAsProvider ? [
                'amount' => currency_format($voucher->amount_available),
                'amount_locale' => currency_format_locale($voucher->amount_available),
                'amount_visible' => $fund->fund_config?->voucher_amount_visible,
            ] : [],
            ...$this->makeTimestamps($this->resource->only([
                'created_at',
            ])),
        ];
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

        return [
            ...$voucher->only([
                'fund_id',
            ]),
            'type' => 'product',
            'address' => $voucherToken->address,
            'fund' => $this->fundDetails($voucher->fund),
            'allowed_organizations' => $this->getAllowedOrganizations($voucher, $identityAddress),
            'product' => ProviderProductResource::create($voucher->product, [
                'voucher' => $voucher,
            ]),
            ...$this->makeTimestamps($this->resource->only([
                'created_at',
            ])),
        ];
    }

    /**
     * @param Fund $fund
     * @return array
     */
    protected function fundDetails(Fund $fund): array
    {
        return [
            ...$fund->only([
                'id', 'name', 'state', 'type',
            ]),
            'organization' => (new OrganizationResource($fund->organization))->toArray($this->request),
            'logo' => (new MediaResource($fund->logo))->toArray($this->request),
        ];
    }

    /**
     * @param Voucher $voucher
     * @param string|null $identityAddress
     * @return Collection
     */
    protected function getAllowedOrganizations(Voucher $voucher, ?string $identityAddress): Collection
    {
        $builder = Organization::where(static function (Builder $builder) use ($voucher, $identityAddress) {
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
        $builder = Organization::where(static function (Builder $builder) use ($voucher, $identityAddress) {
            // Has products available for purchase
            $builder->where(function (Builder $builder) use ($voucher, $identityAddress) {
                OrganizationQuery::whereHasPermissions($builder, $identityAddress, Permission::SCAN_VOUCHERS);

                // Product approved to be bought by target voucher
                $builder->whereHas('products', function (Builder $builder) use ($voucher) {
                    ProductQuery::whereAvailableForVoucher($builder, $voucher, null, false);
                });
            });

            foreach ($voucher->product_vouchers as $product_voucher) {
                // Or where at least one of the product vouchers is available for usage
                $builder->orWhere(function (Builder $builder) use ($identityAddress, $product_voucher) {
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
        $organizations = $builder->with(['logo', ...MediaResource::load('logo')])->orderBy('name')->get();

        return $organizations->map(fn (Organization $organization) => [
            ...$organization->only([
                'id', 'name',
            ]),
            'logo' => (new MediaResource($organization->logo))->toArray($this->request),
        ]);
    }

    /**
     * @return VoucherToken|null
     */
    protected function getVoucherToken(): ?VoucherToken
    {
        if ($this->resource instanceof VoucherToken) {
            return $this->resource;
        } elseif ($this->resource instanceof Voucher) {
            return $this->resource->token_without_confirmation?->setRelation('voucher', $this->resource);
        }

        return null;
    }
}
