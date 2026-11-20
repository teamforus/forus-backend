<?php

namespace App\Http\Resources\Traits;

use App\Models\Fund;
use App\Models\FundProviderProduct;
use App\Models\Product;
use Illuminate\Support\Collection;

trait ResolvesProductFeatures
{
    /**
     * @param Product $product
     * @param Fund $fund
     * @param FundProviderProduct|null $deal
     * @return array
     */
    protected function productFundFeatures(Product $product, Fund $fund, ?FundProviderProduct $deal): array
    {
        $reservationsEnabled = $product->reservationsEnabled();
        $reservationsExtraPaymentEnabled = $reservationsEnabled && $product->reservationExtraPaymentsEnabled($fund);

        $scanningEnabled =
            $product->qr_enabled &&
            $product->organization->fund_providers
                ->where('allow_budget', true)
                ->where('fund_id', $fund->id)
                ->isNotEmpty() &&
            (!$deal || $deal->allow_scanning);

        return [
            'feature_scanning_enabled' => $scanningEnabled,
            'feature_reservations_enabled' => $reservationsEnabled,
            'feature_reservation_extra_payments_enabled' => $reservationsExtraPaymentEnabled,
        ];
    }

    /**
     * @param Collection $fundFeatures
     * @return array
     */
    protected function productFeatures(Collection $fundFeatures): array
    {
        return [
            'feature_scanning_enabled' => $fundFeatures->contains('feature_scanning_enabled', true),
            'feature_reservations_enabled' => $fundFeatures->contains('feature_reservations_enabled', true),
            'feature_reservation_extra_payments_enabled' =>
                $fundFeatures->contains('feature_reservation_extra_payments_enabled', true),
        ];
    }
}
