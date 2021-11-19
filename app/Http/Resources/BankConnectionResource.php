<?php

namespace App\Http\Resources;

use App\Models\BankConnection;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read BankConnection $resource
 */
class BankConnectionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return array_merge($this->resource->only('id', 'state'), [
            'iban' => $this->resource->monetary_account_iban,
            'state_locale' => trans( "bank-connections.states." . $this->resource->state),
            'created_at' => $this->resource->created_at->format('Y-m-d H:i:s'),
            'created_at_locale' => format_datetime_locale($this->resource->created_at),
        ]);
    }
}
