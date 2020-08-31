<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\MediaResource;
use App\Http\Resources\OrganizationBasicResource;
use App\Http\Resources\ProductCategoryResource;
use App\Models\FundProvider;
use App\Models\FundProviderChatMessage;
use App\Models\FundProviderProduct;
use App\Models\Product;
use App\Scopes\Builders\FundProviderProductQuery;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class SponsorVoucherResource
 * @property Product $resource
 * @package App\Http\Resources\Sponsor
 */
class SponsorProviderProductResource extends Resource
{
    /**
     * @var string[]
     */
    public static $load = [
        'photo.presets',
        'vouchers_reserved',
        'product_category.translations',
        'organization.logo.presets',
        'organization.business_type.translations',
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

        $oldDeals = FundProviderProductQuery::withTrashed(
            $fundProvider->fund_provider_products()->getQuery()
        )->orderByDesc('created_at')->where([
            'product_id' => $product->id
        ])->withCount('voucher_transactions')->get();

        return array_merge($product->only([
            'id', 'name', 'description', 'product_category_id', 'sold_out',
            'organization_id'
        ]), [
            'description_html' => $product->description_html,
            'organization' => new OrganizationBasicResource($product->organization),
            'total_amount' => $product->total_amount,
            'unlimited_stock' => $product->unlimited_stock,
            'reserved_amount' => $product->vouchers_reserved->count(),
            'sold_amount' => $product->countSold(),
            'stock_amount' => $product->stock_amount,
            'price' => currency_format($product->price),
            'old_price' => $product->old_price ? currency_format($product->old_price) : null,
            'expire_at' => $product->expire_at->format('Y-m-d'),
            'expire_at_locale' => format_date_locale($product->expire_at ?? null),
            'expired' => $product->expired,
            'deleted_at' => $product->deleted_at ? $product->deleted_at->format('Y-m-d') : null,
            'deleted_at_locale' => format_date_locale($product->deleted_at ?? null),
            'deleted' => !is_null($product->deleted_at),
            'photo' => new MediaResource($product->photo),
            'product_category' => new ProductCategoryResource($product->product_category),
            'has_chats' => $product->fund_provider_chats()->exists(),
            'unseen_messages' => FundProviderChatMessage::whereIn(
                'fund_provider_chat_id', $product->fund_provider_chats()->pluck('id')
            )->where('provider_seen', '=', false)->count(),
        ], $fundProvider->fund->isTypeSubsidy() ? [
            'deals_history' => $oldDeals->map(static function(
                FundProviderProduct $fundProviderProduct
            ) {
                return array_merge($fundProviderProduct->only(array_merge([
                    'id', 'amount', 'limit_total', 'limit_per_identity',
                    'voucher_transactions_count',
                ])), [
                    'active' => !$fundProviderProduct->trashed()
                ]);
            })
        ] : []);
    }
}
