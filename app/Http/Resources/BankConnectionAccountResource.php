<?php

namespace App\Http\Resources;

use App\Models\BankConnectionAccount;

/**
 * @property-read BankConnectionAccount $resource
 */
class BankConnectionAccountResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return $this->resource->only([
            'id', 'bank_connection_id', 'monetary_account_id', 'monetary_account_iban',
            'default', 'type',
        ]);
    }
}
