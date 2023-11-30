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
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return
            $this->isAuthenticated() &&
            $this->organization->identityCan($this->identity(), 'view_funds_extra_payments');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
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
