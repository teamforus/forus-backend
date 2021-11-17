<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds\FundProviders\FundsProviderChats;

use App\Http\Requests\BaseFormRequest;

class StoreFundProviderChatMessageRequest extends BaseFormRequest
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
            'message' => 'required|string|min:1|max:2000',
        ];
    }
}
