<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds\Identities;

use App\Http\Requests\BaseFormRequest;

class IndexIdentitiesRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $orderFields = implode(',', [
            'id', 'email', 'count_vouchers',
            'count_vouchers_active', 'count_vouchers_active_with_balance',
        ]);

        return [
            'per_page' => $this->perPageRule(),
            'target' => 'nullable|in:all,has_balance,self',
            'has_email' => 'nullable|boolean',
            'order_by' => 'nullable|in:' . $orderFields,
            'order_dir' => 'nullable|in:asc,desc',
            'with_reservations' => 'nullable|boolean',
        ];
    }
}
