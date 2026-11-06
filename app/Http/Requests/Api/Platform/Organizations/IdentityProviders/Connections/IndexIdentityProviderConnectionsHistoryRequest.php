<?php

namespace App\Http\Requests\Api\Platform\Organizations\IdentityProviders\Connections;

use App\Http\Requests\BaseFormRequest;

class IndexIdentityProviderConnectionsHistoryRequest extends BaseFormRequest
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'page' => 'nullable|integer|min:1',
            'per_page' => $this->perPageRule(),
        ];
    }
}
