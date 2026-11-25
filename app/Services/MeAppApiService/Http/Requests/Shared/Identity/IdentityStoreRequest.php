<?php

namespace App\Services\MeAppApiService\Http\Requests\Shared\Identity;

use App\Http\Requests\Api\IdentityStoreRequest as LegacyIdentityStoreRequest;

class IdentityStoreRequest extends LegacyIdentityStoreRequest
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                ...$this->emailRules(),
            ],
        ];
    }
}
