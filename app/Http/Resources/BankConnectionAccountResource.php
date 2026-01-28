<?php

namespace App\Http\Resources;

use App\Models\BankConnectionAccount;
use Illuminate\Http\Request;

/**
 * @property-read BankConnectionAccount $resource
 */
class BankConnectionAccountResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return $this->resource->only([
            'id', 'bank_connection_id', 'monetary_account_id', 'monetary_account_iban',
            'default', 'type',
        ]);
    }
}
