<?php

namespace App\Http\Requests\Api\Platform;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class SearchProvidersRequest extends BaseFormRequest
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
        $implementation = $this->implementation_model();

        return [
            'per_page'  => 'numeric|max:1000',
            'fund_id'   => [
                $implementation->isGeneral() || !$implementation ? null : (
                    Rule::in($implementation->funds()->pluck('funds.id'))
                )
            ],
            'business_type_id'   => [
                Rule::exists('business_types', 'id')
            ],
            'order_by'              => 'nullable|in:created_at',
            'order_by_dir'          => 'nullable|in:asc,desc',
        ];
    }
}
