<?php

namespace App\Http\Resources;

use App\Models\BankConnection;
use App\Services\BankService\Resources\BankResource;

/**
 * @property-read BankConnection $resource
 */
class BankConnectionResource extends BaseJsonResource
{
    public const LOAD = [
        'bank_connection_accounts',
        'bank_connection_default_account',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return array_merge($this->resource->only('id', 'state', 'organization_id'), [
            'bank' => new BankResource($this->resource->bank),
            'iban' => $this->resource->iban,
            'account_default' => new BankConnectionAccountResource($this->resource->bank_connection_default_account),
            'accounts' => BankConnectionAccountResource::collection($this->resource->bank_connection_accounts),
            'state_locale' => trans( "bank-connections.states." . $this->resource->state),
            'created_at' => $this->resource->created_at->format('Y-m-d H:i:s'),
            'created_at_locale' => format_datetime_locale($this->resource->created_at),
        ], $this->resource->isPending() && $this->resource->auth_url ? [
            'auth_url' => $this->resource->auth_url,
        ] : []);
    }
}
