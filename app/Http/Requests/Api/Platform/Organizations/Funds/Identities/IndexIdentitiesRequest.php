<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds\Identities;

use App\Http\Requests\BaseFormRequest;

class IndexIdentitiesRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{per_page: string, target: 'nullable|in:providers_all,providers_approved,providers_rejected,all,has_balance,self', has_email: 'nullable|boolean', order_by: string, order_dir: 'nullable|in:asc,desc', with_reservations: 'nullable|boolean'}
     */
    public function rules(): array
    {
        $orderFields = implode(',', [
            'id', 'email', 'count_vouchers',
            'count_vouchers_active', 'count_vouchers_active_with_balance',
        ]);

        return [
            'per_page' => $this->perPageRule(),
            'target' => 'nullable|in:providers_all,providers_approved,providers_rejected,all,has_balance,self',
            'has_email' => 'nullable|boolean',
            'order_by' => 'nullable|in:' . $orderFields,
            'order_dir' => 'nullable|in:asc,desc',
            'with_reservations' => 'nullable|boolean',
        ];
    }
}
