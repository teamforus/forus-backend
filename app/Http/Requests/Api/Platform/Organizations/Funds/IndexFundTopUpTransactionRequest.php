<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds;

use App\Http\Requests\BaseFormRequest;

class IndexFundTopUpTransactionRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{q: 'nullable|string|max:100', from: 'date_format:Y-m-d', to: 'date_format:Y-m-d', amount_min: 'numeric|min:0', amount_max: 'numeric|min:0', per_page: string, order_by: 'nullable|in:code,iban,amount,created_at', order_dir: 'nullable|in:asc,desc'}
     */
    public function rules(): array
    {
        return [
            'q'                 => 'nullable|string|max:100',
            'from'              => 'date_format:Y-m-d',
            'to'                => 'date_format:Y-m-d',
            'amount_min'        => 'numeric|min:0',
            'amount_max'        => 'numeric|min:0',
            'per_page'          => $this->perPageRule(),
            'order_by'          => 'nullable|in:code,iban,amount,created_at',
            'order_dir'         => 'nullable|in:asc,desc',
        ];
    }
}
