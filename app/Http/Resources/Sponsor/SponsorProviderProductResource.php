<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\MediaResource;
use App\Http\Resources\OrganizationBasicResource;
use App\Http\Resources\ProductCategoryResource;
use App\Models\FundProvider;
use App\Models\FundProviderChatMessage;
use App\Models\FundProviderProduct;
use App\Models\Product;
use App\Scopes\Builders\FundProviderProductQuery;

/**
 * @property Product $resource
 */
class SponsorProviderProductResource extends BaseJsonResource
{
    public const LOAD = [
        'photo.presets',
        'product_reservations_pending',
        'product_category.translations',
        'organization.logo.presets',
        'organization.business_type.translations',
        'sponsor_organization.logo.presets',
        'sponsor_organization.business_type.translations',
        'product_exclusions',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var FundProvider $fundProvider */
        $fundProvider = $request->route('fund_provider');
        $product = $this->resource;

        return array_merge($product->only([
            'id', 'name', 'description', 'product_category_id', 'sold_out',
            'organization_id', 'price_type', 'price_type_discount', 'sponsor', 'sponsor_organization_id',
            'reservation_enabled', 'reservation_policy', 'alternative_text',
        ]), [
            'description_html' => $product->description_html,
            'organization' => new OrganizationBasicResource($product->organization),
            'sponsor_organization' => new OrganizationBasicResource($this->resource->sponsor_organization),
            'total_amount' => $product->total_amount,
            'unlimited_stock' => $product->unlimited_stock,
            'reserved_amount' => $product->countReservedCached($fundProvider?->fund),
            'sold_amount' => $product->countSold($fundProvider?->fund),
            'stock_amount' => $product->stock_amount,
            'price' => currency_format($product->price),
            'price_locale' => $product->price_locale,
            'price_type' => $product->price_type,
            'price_discount' => $product->price_discount ? currency_format($product->price_discount) : null,
            'expire_at' => $product->expire_at ? $product->expire_at->format('Y-m-d') : '',
            'expire_at_locale' => format_date_locale($product->expire_at ?? null),
            'expired' => $product->expired,
            'deleted_at' => $product->deleted_at ? $product->deleted_at->format('Y-m-d') : null,
            'deleted_at_locale' => format_date_locale($product->deleted_at ?? null),
            'deleted' => !is_null($product->deleted_at),
            'photo' => new MediaResource($product->photo),
            'product_category' => new ProductCategoryResource($product->product_category),
            'unseen_messages' => $this->hasUnseenMessages($product),
            'is_available' => $this->isAvailable($product, $fundProvider) ,
            'deals_history' => $fundProvider ? $this->getDealsHistory($product, $fundProvider) : null,
        ], $this->productReservationFieldSettings($product));
    }

    /**
     * @param Product $product
     * @return bool
     */
    protected function hasUnseenMessages(Product $product): bool
    {
        return FundProviderChatMessage::whereIn(
            'fund_provider_chat_id', $product->fund_provider_chats()->pluck('id')
        )->where('provider_seen', '=', false)->count();
    }

    /**
     * @param Product $product
     * @param FundProvider|null $fundProvider
     * @return bool
     */
    protected function isAvailable(Product $product, ?FundProvider $fundProvider = null): bool
    {
        return !$fundProvider || $product->product_exclusions
            ->where('fund_provider_id', $fundProvider->id)
            ->isEmpty();
    }

    /**
     * @param Product $product
     * @param FundProvider|null $fundProvider
     * @return array
     */
    protected function getDealsHistory(Product $product, ?FundProvider $fundProvider = null): array
    {
        $dealsHistoryQuery = $fundProvider->fund_provider_products()
            ->where('product_id', $product->id)
            ->withCount([
                'voucher_transactions',
                'product_reservations_pending',
            ])
            ->withTrashed()
            ->latest();

        if (!$fundProvider->fund->isTypeSubsidy()) {
            FundProviderProductQuery::whereConfigured($dealsHistoryQuery);
        }

        return $dealsHistoryQuery->get()->map(fn (FundProviderProduct $product) => array_merge($product->only([
            'id', 'amount', 'limit_total', 'limit_total_unlimited', 'limit_per_identity',
            'voucher_transactions_count', 'product_reservations_pending_count', 'active', 'product_id',
        ]), [
            'expire_at' => $product->expire_at?->format('Y-m-d'),
            'expire_at_locale' => format_date_locale($product->expire_at),
        ]))->toArray();
    }

    /**
     * @param Product $product
     * @return array
     */
    private function productReservationFieldSettings(Product $product): array
    {
        return [
            'reservation_phone' => $product->reservation_phone,
            'reservation_address' => $product->reservation_address,
            'reservation_birth_date' => $product->reservation_birth_date,
        ];
    }
}