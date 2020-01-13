<?php

namespace App\Http\Requests\Api\Platform;

use App\Models\Implementation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchProvidersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'per_page'  => 'numeric|max:1000',
            'fund_id'   => [
                Implementation::activeKey() == 'general' ? null : (
                    Rule::in(Implementation::activeModel()->funds()->pluck('funds.id'))
                )
            ],
            'business_type_id'   => [
                Rule::exists('business_types', 'id')
            ]
        ];
    }
}
