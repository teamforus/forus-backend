<?php

namespace App\Http\Resources;

use App\Models\BankConnection;
use App\Services\BankService\Resources\BankResource;

/**
 * @property-read BankConnection $resource
 */
class BankConnectionResource extends BaseJsonResource
{
    public const array LOAD = [
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
        ], $this->resource->isPending() && $this->resource->auth_url ? [
            'auth_url' => $this->resource->auth_url,
        ] : [], array_merge(
            $this->makeTimestamps($this->resource->only(['created_at', 'updated_at'])),
            $this->makeTimestamps($this->resource->only(['expire_at']), true),
        ));
    }
}
