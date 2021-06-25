<?php

namespace App\Http\Requests\Api\Platform\Vouchers;

use App\Http\Requests\BaseFormRequest;

/**
 * Class IndexVouchersRequest
 * @package App\Http\Requests\Api\Platform\Vouchers
 */
class IndexVouchersRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return !empty($this->auth_address());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'per_page' => 'nullable|numeric|between:1,100',
            'product_id' => 'nullable|exists:products,id'
        ];
    }
}
