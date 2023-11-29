<?php

namespace App\Http\Requests\Api\Platform\PreChecks;

use App\Http\Requests\BaseFormRequest;

class IndexPreCheckRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'q' => 'nullable|string',
            'organization_id' => 'nullable|exists:organizations,id',
            'tag' => 'nullable|string|exists:tags,key',
        ];
    }
}
