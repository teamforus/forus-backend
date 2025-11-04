<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\ExtraPayments;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\Permission;

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
            $this->organization->identityCan($this->identity(), Permission::VIEW_FUNDS_EXTRA_PAYMENTS);
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
            ]),
        ];
    }
}
