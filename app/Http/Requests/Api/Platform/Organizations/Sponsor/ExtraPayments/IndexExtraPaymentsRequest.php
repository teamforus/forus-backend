<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\ExtraPayments;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;

/**
 * @property Organization $organization
 */
class IndexExtraPaymentsRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return (mixed|string)[]
     *
     * @psalm-return array{q: 'nullable|string'|mixed, fund_id: 'nullable|exists:funds,id'|mixed,...}
     */
    public function rules(): array
    {
        return [
            'q' => 'nullable|string',
            'fund_id' => 'nullable|exists:funds,id',
            ...$this->sortableResourceRules(100, [
                'id', 'price', 'amount', 'method', 'paid_at', 'fund_name',
                'product_name', 'provider_name',
            ])
        ];
    }
}
