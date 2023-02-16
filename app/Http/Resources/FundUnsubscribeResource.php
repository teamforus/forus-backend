<?php

namespace App\Http\Resources;

use App\Models\FundUnsubscribe;

/**
 * @property FundUnsubscribe $resource
 */
class FundUnsubscribeResource extends BaseJsonResource
{
    public const LOAD = [
        'fund_provider'
    ];

    /**
     * Transform the resource into an array.
     *
     * @param $request
     * @return array
     */
    public function toArray($request): array
    {
        $fundUnsubscribe = $this->resource;
        $translationKey  = $fundUnsubscribe->is_expired ? 'expired' : $fundUnsubscribe->state;

        return array_merge($fundUnsubscribe->only([
            'id', 'fund_provider_id', 'note', 'unsubscribe_date', 'state'
        ]), [
            'fund_provider'             => new FundProviderResource($fundUnsubscribe->fund_provider),
            'created_at_locale'         => format_date_locale($fundUnsubscribe->created_at),
            'unsubscribe_date_locale'   => format_date_locale($fundUnsubscribe->unsubscribe_date),
            'unsubscribe_days_left'     => now()->diffInDays($fundUnsubscribe->unsubscribe_date),
            'is_expired'                => $fundUnsubscribe->is_expired,
            'state_locale'              => trans("fund-unsubscribes.states.$translationKey")
        ]);
    }
}
