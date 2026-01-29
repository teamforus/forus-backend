<?php

namespace App\Http\Resources;

use App\Models\BankConnection;
use App\Services\BankService\Resources\BankResource;
use Illuminate\Http\Request;

/**
 * @property-read BankConnection $resource
 */
class BankConnectionResource extends BaseJsonResource
{
    public const array LOAD = [
        'bank',
    ];

    public const array LOAD_NESTED = [
        'bank_connection_accounts' => BankConnectionAccountResource::class,
        'bank_connection_default_account' => BankConnectionAccountResource::class,
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return array_merge($this->resource->only('id', 'state', 'organization_id'), [
            'bank' => new BankResource($this->resource->bank),
            'iban' => $this->resource->iban,
            'account_default' => new BankConnectionAccountResource($this->resource->bank_connection_default_account),
            'accounts' => BankConnectionAccountResource::collection($this->resource->bank_connection_accounts),
            'state_locale' => trans('bank-connections.states.' . $this->resource->state),
        ], $this->resource->isPending() && $this->resource->auth_url ? [
            'auth_url' => $this->resource->auth_url,
        ] : [], array_merge(
            $this->makeTimestamps($this->resource->only(['created_at', 'updated_at'])),
            $this->makeTimestamps($this->resource->only(['expire_at']), true),
        ));
    }
}
