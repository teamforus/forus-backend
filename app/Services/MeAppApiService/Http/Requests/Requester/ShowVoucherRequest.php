<?php

namespace App\Services\MeAppApiService\Http\Requests\Requester;

use App\Http\Requests\BaseFormRequest;

class ShowVoucherRequest extends BaseFormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'product_id' => 'nullable|integer|exists:products,id',
        ];
    }
}
