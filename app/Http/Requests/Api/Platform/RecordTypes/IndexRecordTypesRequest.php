<?php

namespace App\Http\Requests\Api\Platform\RecordTypes;

use App\Http\Requests\BaseFormRequest;

class IndexRecordTypesRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{criteria: 'nullable|boolean', vouchers: 'nullable|boolean', organization_id: 'nullable|exists:organizations,id'}
     */
    public function rules(): array
    {
        return [
            'criteria' => 'nullable|boolean',
            'vouchers' => 'nullable|boolean',
            'organization_id' => 'nullable|exists:organizations,id',
        ];
    }
}
