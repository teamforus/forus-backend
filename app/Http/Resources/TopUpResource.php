<?php

namespace App\Http\Resources;

use App\Models\FundTopUp;

/**
 * Class TopUpResource
 * @property FundTopUp $resource
 * @package App\Http\Resources
 */
class TopUpResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|mixed
     * @throws \Exception
     */
    public function toArray($request): array
    {
        $bankConnection = $this->resource->fund->organization->bank_connection_active;
        $bankConnectionAccount = $bankConnection->bank_connection_default_account;

        return array_merge($this->resource->only('id', 'code', 'state'), [
            'iban' => $bankConnectionAccount ? $bankConnectionAccount->monetary_account_iban : null,
        ], $this->timestamps($this->resource, 'created_at'));
    }
}
