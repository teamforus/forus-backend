<?php

namespace App\Services\WalletService\Resources;

use App\Http\Resources\BaseJsonResource;
use App\Services\WalletService\Models\WalletFlow;
use Illuminate\Http\Request;

/**
 * @property-read WalletFlow $resource
 */
class WalletFlowResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return ?array
     */
    public function toArray(Request $request): ?array
    {
        return $this->resource?->only([
            'id', 'provider', 'key', 'name',
        ]);
    }
}
