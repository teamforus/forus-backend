<?php

namespace App\Http\Resources;

use App\Models\BankConnectionAccount;
use App\Models\FundTopUpTransaction;

/**
 * Class TopUpTransactionResource
 * @property FundTopUpTransaction $resource
 * @package App\Http\Resources
 */
class TopUpTransactionResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $topUpTransaction = $this->resource;

        return array_merge($topUpTransaction->only('id', 'fund_top_up_id', 'amount', 'created_at'), [
            'code' => $topUpTransaction->fund_top_up->code,
            'iban' => $topUpTransaction->bank_connection_account?->monetary_account_iban,
            'created_at_locale' => format_datetime_locale($topUpTransaction->created_at),
            'fund_name' => $topUpTransaction->fund_top_up->fund->name,
            'fund_organization_name' => $topUpTransaction->fund_top_up->fund->organization->name,
        ]);
    }
}
