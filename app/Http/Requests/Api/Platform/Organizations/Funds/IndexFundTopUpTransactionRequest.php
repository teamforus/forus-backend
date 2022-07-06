<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds;

use App\Http\Requests\BaseFormRequest;

class IndexFundTopUpTransactionRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return !empty($this->auth_address());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q'                 => 'nullable|string|max:100',
            'code'              => 'nullable|string|max:50',
            'iban'              => 'nullable|string|max:50',
            'from'              => 'date:Y-m-d',
            'to'                => 'date:Y-m-d',
            'amount_min'        => 'numeric|min:0',
            'amount_max'        => 'numeric|min:0',
            'per_page'          => $this->perPageRule(),
        ];
    }
}
