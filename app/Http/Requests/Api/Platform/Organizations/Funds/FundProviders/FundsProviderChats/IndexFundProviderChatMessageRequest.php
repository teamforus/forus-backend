<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds\FundProviders\FundsProviderChats;

use Illuminate\Foundation\Http\FormRequest;

class IndexFundProviderChatMessageRequest extends FormRequest
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
            'per_page' => [
                'nullable',
                'numeric',
                'max:100'
            ],
        ];
    }
}
