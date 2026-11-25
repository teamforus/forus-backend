<?php

namespace App\Http\Requests\Api\Platform\Organizations\Products\FundProviderChats;

use App\Http\Requests\BaseFormRequest;

class IndexFundProviderChatRequest extends BaseFormRequest
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'fund_id' => 'nullable|integer|min:1',
        ];
    }
}
