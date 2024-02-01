<?php

namespace App\Http\Resources;

use App\Http\Resources\Small\FundSmallResource;
use App\Models\FundProviderUnsubscribe;

/**
 * @property FundProviderUnsubscribe $resource
 */
class FundProviderUnsubscribeResource extends BaseJsonResource
{
    /**
     * @param string|null $append
     * @return array
     */
    public static function load(?string $append = null): array
    {
        return array_merge(parent::load($append), FundProviderResource::load('fund_provider'));
    }

    /**
     * Transform the resource into an array.
     *
     * @param $request
     * @return array
     */
    public function toArray($request): array
    {
        $fundUnsubscribe = $this->resource;

        return array_merge($fundUnsubscribe->only([
            'id', 'fund_provider_id', 'note', 'unsubscribe_at', 'state', 'state_locale'
        ]), [
            'fund'                      => new FundSmallResource($fundUnsubscribe->fund_provider->fund),
            'is_expired'                => $fundUnsubscribe->is_expired,
            'can_cancel'                => $fundUnsubscribe->isPending() && $fundUnsubscribe->fund_provider->isAccepted(),
            'fund_provider'             => new FundProviderResource($fundUnsubscribe->fund_provider),
            'unsubscribe_at'            => $fundUnsubscribe->unsubscribe_at?->format('Y-m-d'),
            'unsubscribe_at_locale'     => format_date_locale($fundUnsubscribe->unsubscribe_at),
            'unsubscribe_days_left'     => now()->diffInDays($fundUnsubscribe->unsubscribe_at),
        ], $this->timestamps($fundUnsubscribe, 'created_at'));
    }
}
