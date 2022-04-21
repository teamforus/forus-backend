<?php

namespace App\Http\Resources;

use App\Models\FundTopUp;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class TopUpResource
 * @property FundTopUp $resource
 * @package App\Http\Resources
 */
class TopUpResource extends Resource
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
            'created_at' => $this->resource->created_at->format('Y-m-d H:i:s'),
            'created_at_locale' => format_datetime_locale($this->resource->created_at),
        ]);
    }
}
