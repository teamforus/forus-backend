<?php

namespace App\Http\Requests\Api\Platform\IdentityProviders;

use App\Http\Requests\BaseFormRequest;

class CompleteIdentityProviderLinkRequest extends BaseFormRequest
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'exchange_token' => 'required|string|max:100',
            'browser_token' => 'required|string|size:64',
        ];
    }
}
