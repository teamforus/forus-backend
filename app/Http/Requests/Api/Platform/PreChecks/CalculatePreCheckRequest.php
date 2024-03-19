<?php

namespace App\Http\Requests\Api\Platform\PreChecks;

use App\Http\Requests\BaseFormRequest;

class CalculatePreCheckRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{q: 'nullable|string', tag_id: 'nullable|exists:tags,id', organization_id: 'nullable|exists:organizations,id', records: 'required|array', 'records.*.key': 'required|string', 'records.*.value': 'nullable|string|min:0|max:200'}
     */
    public function rules(): array
    {
        return [
            'q' => 'nullable|string',
            'tag_id' => 'nullable|exists:tags,id',
            'organization_id' => 'nullable|exists:organizations,id',
            'records' => 'required|array',
            'records.*.key' => 'required|string',
            'records.*.value' => 'nullable|string|min:0|max:200',
        ];
    }
}
