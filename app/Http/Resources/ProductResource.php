<?php

namespace App\Http\Resources;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\Product;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\ProductSubQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * @property Product $resource
 */
class ProductResource extends BaseJsonResource
{
    public const LOAD = [
        'voucher_transactions',
        'product_reservations_pending',
        'photo.presets',
        'product_category.translations',
        'organization.offices.photo.presets',
        'organization.offices.schedules',
        'organization.offices.organization',
        'organization.offices.organization.logo.presets',
        'organization.logo.presets',
        'organization.business_type.translations',
        'organization.fund_providers_allowed_extra_payments',
        'organization.mollie_connection',
        'bookmarks',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $baseRequest = BaseFormRequest::createFrom($request);
        $product = $this->resource;
        $simplified = $request->has('simplified') && $request->input('simplified');
        $baseFields = $this->baseFields($product);

        return $simplified ? $baseFields : array_merge($baseFields, [
            'total_amount' => $product->total_amount,
            'unlimited_stock' => $product->unlimited_stock,
            'reserved_amount' => $product->countReservedCached(),
            'sold_amount' => $product->countSold(),
            'stock_amount' => $product->stock_amount,
            'expire_at' => $product->expire_at?->format('Y-m-d'),
            'expire_at_locale' => format_date_locale($product->expire_at ?? null),
            'expired' => $product->expired,
            'deleted_at' => $product->deleted_at?->format('Y-m-d'),
            'deleted_at_locale' => format_date_locale($product->deleted_at ?? null),
            'deleted' => $product->trashed(),
            'funds' => $product->trashed() ? [] : $this->getProductFunds($baseRequest, $product),
            'offices' => OfficeResource::collection($product->organization->offices),
            'product_category' => new ProductCategoryResource($product->product_category),
            'bookmarked' => $product->isBookmarkedBy($baseRequest->identity()),
        ], array_merge(
            $this->priceFields($product),
            $this->productReservationFieldSettings($product),
        ));
    }

    /**
     * @param Product $product
     * @return array
     */
    protected function baseFields(Product $product): array
    {
        return array_merge($product->only([
            'id', 'name', 'description', 'description_html', 'product_category_id', 'sold_out',
            'organization_id', 'reservation_enabled', 'reservation_policy', 'alternative_text',
        ]), [
            'photo' => new MediaResource($product->photo),
            'price' => is_null($product->price) ? null : currency_format($product->price),
            'price_locale' => $product->price_locale,
            'organization' => new OrganizationBasicResource($product->organization),
        ]);
    }

    /**
     * @param Product $product
     * @return array
     */
    protected function priceFields(Product $product): array {
        $price_min = $this->getProductSubsidyPrice($product, 'max');
        $price_max = $this->getProductSubsidyPrice($product, 'min');
        $lowest_price = min($product->price, $price_min);

        return [
            'price_type' => $product->price_type,
            'price_discount' => $product->price_discount ? currency_format($product->price_discount) : null,
            'price_discount_locale' => $product->price_discount_locale,
            'price_min' => currency_format($price_min),
            'price_min_locale' => currency_format_locale($price_min),
            'price_max' => currency_format($price_max),
            'price_max_locale' => currency_format_locale($price_max),
            'lowest_price' => $product->price_type === 'regular' ? currency_format($lowest_price) : null,
            'lowest_price_locale' => $product->price_type === 'regular' ? currency_format_locale($lowest_price) : null,
        ];
    }

    /**
     * @return Builder
     */
    protected function fundsQuery(): Builder
    {
        return Fund::query();
    }

    /**
     * @param BaseFormRequest $request
     * @param Product $product
     * @return Collection
     */
    private function getProductFunds(BaseFormRequest $request, Product $product): Collection
    {
        $fundsQuery = FundQuery::whereProductsAreApprovedAndActiveFilter($this->fundsQuery(), $product);
        $fundsQuery->with([
            'organization', 'logo',
        ]);

        return $fundsQuery->get()->map(function(Fund $fund) use ($product, $request) {
            $data = [
                'id' => $fund->id,
                'name' => $fund->name,
                'logo' => new MediaResource($fund->logo),
                'type' => $fund->type,
                'organization' => $fund->organization->only('id', 'name'),
                'end_at' => $fund->end_date?->format('Y-m-d'),
                'end_at_locale' => format_date_locale($fund->end_date ?? null),
                'reservations_enabled' => $product->reservationsEnabled($fund),
                'reservation_extra_payments_enabled' => $product->reservationExtraPaymentsEnabled($fund),
            ];

            $productData = ProductSubQuery::appendReservationStats([
                'identity_address' => $request->auth_address(),
                'fund_id' => $fund->id,
            ], Product::whereId($product->id))->firstOrFail()->only([
                'limit_total', 'limit_per_identity', 'limit_available',
            ]);

            $fundProviderProduct = $product->getFundProviderProduct($fund);

            return array_merge($data, $productData, [
                'limit_per_identity' => $fundProviderProduct?->limit_per_identity,
            ], $fund->isTypeSubsidy() ? [
                'price' => $fundProviderProduct->user_price,
                'price_locale' => $fundProviderProduct->user_price_locale,
            ] : []);
        })->values();
    }

    /**
     * @param Product $product
     * @param string $type
     * @return float
     */
    private function getProductSubsidyPrice(Product $product, string $type): float
    {
        return max($product->price - $product->fund_provider_products()->where([
            'product_id' => $product->id,
        ])->whereHas('fund_provider.fund', function(Builder $builder) {
            $builder->where('funds.type', Fund::TYPE_SUBSIDIES);
            $builder->whereIn('funds.id', $this->fundsQuery()->select('funds.id'));
        })->$type('amount'), 0);
    }

    /**
     * @param Product $product
     * @return array
     */
    private function productReservationFieldSettings(Product $product): array
    {
        $global = $product::RESERVATION_FIELD_GLOBAL;
        $request = BaseFormRequest::createFromBase(request());
        $organization = $product->organization;
        $fields = $organization->reservation_fields;

        if ($request->isWebshop()) {
            return [
                'reservation' => [
                    'phone' => $product->reservation_phone === $global ?
                        $organization->reservation_phone :
                        $product->reservation_phone,
                    'address' => $product->reservation_address === $global ?
                        $organization->reservation_address :
                        $product->reservation_address,
                    'birth_date' => $product->reservation_birth_date === $global ?
                        $organization->reservation_birth_date :
                        $product->reservation_birth_date,
                    'fields' => OrganizationReservationFieldResource::collection($fields)
                ],
            ];
        }

        return [
            'reservation_phone' => $product->reservation_phone,
            'reservation_address' => $product->reservation_address,
            'reservation_birth_date' => $product->reservation_birth_date,
        ];
    }
}
