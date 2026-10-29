<?php

namespace App\Services\MeAppApiService\Http\Resources\Requester;

use App\Http\Resources\BaseJsonResource;
use App\Models\Fund;
use App\Models\FundProviderProduct;
use App\Models\Product;
use App\Services\MeAppApiService\Http\Resources\Shared\MediaResource;
use Illuminate\Http\Request;

/**
 * @property-read Fund $resource
 */
class ProductFundResource extends BaseJsonResource
{
    public const array LOAD_NESTED = [
        'logo' => MediaResource::class,
    ];

    /**
     * @param Fund $fund
     * @param Product $product
     * @param int|null $limitAvailable
     * @param FundProviderProduct|null $fundProviderProduct
     */
    public function __construct(
        Fund $fund,
        private readonly Product $product,
        private readonly ?int $limitAvailable,
        private readonly ?FundProviderProduct $fundProviderProduct,
    ) {
        parent::__construct($fund);
    }

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $fund = $this->resource;
        $product = $this->product;
        $fundProviderProduct = $this->fundProviderProduct;

        $reservationsEnabled = $product->reservationsEnabled();
        $reservationsExtraPaymentEnabled = $reservationsEnabled && $product->reservationExtraPaymentsEnabled($fund);

        $scanningEnabled =
            $product->qr_enabled &&
            $product->organization->fund_providers
                ->where('allow_budget', true)
                ->where('fund_id', $fund->id)
                ->isNotEmpty() &&
            (!$fundProviderProduct || $fundProviderProduct->allow_scanning);

        return [
            ...$fund->only([
                'id',
            ]),
            ...$fund->translateColumns($fund->only([
                'name',
            ])),
            'logo' => $fund->logo ? new MediaResource($fund->logo) : null,
            'limit_available' => $this->limitAvailable,
            ...$fundProviderProduct?->isPaymentTypeSubsidy() ? [
                'user_price' => currency_format($fundProviderProduct->user_price),
                'user_price_locale' => $fundProviderProduct->user_price_locale,
            ] : [
                'user_price' => is_null($product->price) ? null : currency_format($product->price),
                'user_price_locale' => $product->price_locale,
            ],
            'limit_per_identity' => $fundProviderProduct?->limit_per_identity,
            'feature_scanning_enabled' => $scanningEnabled,
            'feature_reservations_enabled' => $reservationsEnabled,
            'feature_reservation_extra_payments_enabled' => $reservationsExtraPaymentEnabled,
            ...$this->makeTimestamps(['end_at' => $fund->end_date], dateOnly: true),
        ];
    }
}
