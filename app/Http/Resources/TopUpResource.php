<?php

namespace App\Http\Resources;

use App\Models\FundTopUp;
use Illuminate\Http\Request;

/**
 * @property FundTopUp $resource
 */
class TopUpResource extends BaseJsonResource
{
    public const LOAD = [
        'fund.organization.bank_connection_active.bank_connection_default_account',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $bankConnection = $this->resource->fund->organization->bank_connection_active;
        $bankConnectionAccount = $bankConnection->bank_connection_default_account;

        return array_merge($this->resource->only('id', 'code', 'state'), [
            'iban' => $bankConnectionAccount?->monetary_account_iban,
        ], $this->timestamps($this->resource, 'created_at'));
    }
}
