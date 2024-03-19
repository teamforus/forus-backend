<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds\FundProviders\FundsProviderChats;

use App\Http\Requests\BaseFormRequest;

class StoreFundProviderChatMessageRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{message: 'required|string|min:1|max:2000'}
     */
    public function rules(): array
    {
        return [
            'message' => 'required|string|min:1|max:2000',
        ];
    }
}
