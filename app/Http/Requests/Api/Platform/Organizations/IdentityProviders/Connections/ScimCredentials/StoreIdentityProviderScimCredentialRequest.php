<?php

namespace App\Http\Requests\Api\Platform\Organizations\IdentityProviders\Connections\ScimCredentials;

use App\Http\Requests\BaseFormRequest;
use App\Services\IdentityProviderService\Models\IdentityProviderMembership;
use Illuminate\Validation\Rule;

class StoreIdentityProviderScimCredentialRequest extends BaseFormRequest
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'account_type' => ['required', Rule::in(IdentityProviderMembership::ACCOUNT_TYPES)],
        ];
    }
}
