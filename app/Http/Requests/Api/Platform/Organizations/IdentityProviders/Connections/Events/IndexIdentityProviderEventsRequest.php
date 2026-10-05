<?php

namespace App\Http\Requests\Api\Platform\Organizations\IdentityProviders\Connections\Events;

use App\Http\Requests\BaseFormRequest;

class IndexIdentityProviderEventsRequest extends BaseFormRequest
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
