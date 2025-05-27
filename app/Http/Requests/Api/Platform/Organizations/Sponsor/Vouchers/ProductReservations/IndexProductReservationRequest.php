<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Vouchers\ProductReservations;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

/**
 * @property Organization|null $organization
 */
class IndexProductReservationRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'voucher_id' => [
                'nullable',
                Rule::exists('vouchers', 'id')->where(function (Builder $query) {
                    $query->whereIn('fund_id', $this->organization->funds()->select('id'));
                }),
            ],
            ...$this->sortableResourceRules(columns: [
                'created_at', 'code', 'product', 'provider', 'price', 'amount_extra', 'customer',
                'created_at', 'state', 'transaction_id', 'transaction_state',
            ]),
        ];
    }
}
