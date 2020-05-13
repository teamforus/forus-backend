<?php

namespace App\Http\Resources;

use App\Models\FundProviderChatMessage;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class FundProviderChatMessageResource
 * @property FundProviderChatMessage $resource
 * @package App\Http\Resources\Models
 */
class FundProviderChatMessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $fund_provider_chat_message = $this->resource;

        return array_merge($this->resource->only([
            'id', 'fund_provider_chat_id', 'message', 'identity_address',
            'counterpart', 'sponsor_seen', 'provider_seen'
        ]), [
            'is_today' => $fund_provider_chat_message->created_at->isToday(),
            'time' => $fund_provider_chat_message->created_at->format('H:i'),
            'date' => format_date_locale(
                $fund_provider_chat_message->created_at
            ),
            'created_at' => $fund_provider_chat_message->created_at->format('Y-m-d'),
            'updated_at' => $fund_provider_chat_message->updated_at->format('Y-m-d'),
            'created_at_locale' => format_date_locale(
                $fund_provider_chat_message->created_at
            ),
            'updated_at_locale' => format_date_locale(
                $fund_provider_chat_message->updated_at
            ),
        ]);
    }
}
