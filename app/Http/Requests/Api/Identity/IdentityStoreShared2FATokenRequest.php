<?php

namespace App\Http\Requests\Api\Identity;

use App\Http\Requests\BaseFormRequest;
use App\Models\Implementation;
use Illuminate\Validation\Rule;

class IdentityStoreShared2FATokenRequest extends BaseFormRequest
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'client_type' => [
                'sometimes',
                'required',
                'string',
                Rule::in([
                    Implementation::ME_APP_REQUESTER_ANDROID, Implementation::ME_APP_REQUESTER_IOS,
                    Implementation::ME_APP_PROVIDER_ANDROID, Implementation::ME_APP_PROVIDER_IOS,
                ]),
            ],
        ];
    }
}
