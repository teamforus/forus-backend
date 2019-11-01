<?php

namespace App\Http\Requests\Api\Platform\Funds\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexFundRequestsRequest extends FormRequest
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
            'per_page' => 'numeric|between:1,100'
        ];
    }
}
