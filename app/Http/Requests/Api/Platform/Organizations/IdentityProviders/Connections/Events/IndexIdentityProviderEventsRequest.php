<?php

namespace App\Http\Requests\Api\Platform\Organizations\IdentityProviders\Connections\Events;

use App\Http\Requests\BaseFormRequest;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use Illuminate\Validation\Rule;

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
            'outcome' => ['nullable', Rule::in(IdentityProviderConnection::EVENT_OUTCOMES)],
            'category' => ['nullable', Rule::in(IdentityProviderConnection::EVENT_CATEGORIES)],
        ];
    }
}
