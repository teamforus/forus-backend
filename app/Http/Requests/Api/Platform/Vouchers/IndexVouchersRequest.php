<?php

namespace App\Http\Requests\Api\Platform\Vouchers;

use App\Http\Requests\BaseFormRequest;
use App\Models\Voucher;

class IndexVouchersRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->isAuthenticated();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'state' => 'nullable|in:' . implode(',', Voucher::STATES),
            'per_page' => 'nullable|numeric|between:1,100',
            'product_id' => 'nullable|exists:products,id',
            'type' => 'nullable|in:' . implode(',', Voucher::TYPES),
            'archived' => 'nullable|boolean'
        ];
    }
}
