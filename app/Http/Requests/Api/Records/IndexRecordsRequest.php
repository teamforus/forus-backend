<?php

namespace App\Http\Requests\Api\Records;

use App\Http\Requests\BaseFormRequest;

class IndexRecordsRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{type: 'nullable|string|exists:record_types,key', deleted: 'nullable|boolean', record_category_id: 'nullable|numeric|exists:record_categories:id'}
     */
    public function rules(): array
    {
        return [
            'type' => 'nullable|string|exists:record_types,key',
            'deleted' => 'nullable|boolean',
            'record_category_id' => 'nullable|numeric|exists:record_categories:id',
        ];
    }
}
