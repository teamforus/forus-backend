<?php

namespace App\Http\Requests\Api\Platform\Funds;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class IndexFundsRequest
 * @package App\Http\Requests\Api\Platform\Funds
 */
class IndexFundsRequest extends FormRequest
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
     * @return array
     */
    public function rules(): array
    {
        return [
            'q' => 'nullable|string',
            'tag' => 'nullable|string|exists:tags,key',
            'state' => 'nullable|in:active_and_closed,active',
            'fund_id' => 'nullable|exists:funds,id',
            'per_page' => 'nullable|numeric|between:1,100',
            'organization_id' => 'nullable|exists:organizations,id',
            'order_by'              => 'nullable|in:created_at',
            'order_by_dir'          => 'nullable|in:asc,desc',
        ];
    }
}
