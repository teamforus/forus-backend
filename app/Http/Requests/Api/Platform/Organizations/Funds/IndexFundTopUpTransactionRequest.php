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
    public function authorize(): bool
    {
        return $this->isAuthenticated();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => 'nullable|string|max:100',
            'from' => 'date_format:Y-m-d',
            'to' => 'date_format:Y-m-d',
            'amount_min' => 'nullable|numeric|min:0',
            'amount_max' => 'nullable|numeric|min:0',
            'per_page' => $this->perPageRule(),
            'order_by' => 'nullable|in:code,iban,amount,created_at',
            'order_dir' => 'nullable|in:asc,desc',
        ];
    }
}
