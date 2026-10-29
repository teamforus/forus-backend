<?php

namespace App\Services\MeAppApiService\Http\Requests\Requester;

use App\Http\Requests\BaseFormRequest;

class IndexFundsRequest extends BaseFormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'has_providers' => 'boolean',
            'page' => $this->pageRule(),
            'per_page' => $this->perPageRule(),
        ];
    }
}
