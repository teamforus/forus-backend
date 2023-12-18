<?php

namespace App\Http\Requests\Api\Platform\PreChecks;

use App\Http\Requests\BaseFormRequest;

class CalculatePreCheckRequest extends BaseFormRequest
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
            'tag' => 'nullable|string|exists:tags,key',
            'tag_id' => 'nullable|string|exists:tags,id',
            'organization_id' => 'nullable|exists:organizations,id',
            'records' => 'required|array',
            'records.*.key' => 'required|string',
            'records.*.value' => 'nullable|string|min:0|max:200',
        ];
    }
}
