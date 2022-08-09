<?php

namespace App\Http\Requests\Api\Records;

use App\Http\Requests\BaseFormRequest;

class IndexRecordsRequest extends BaseFormRequest
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
            'type' => 'nullable|string|exists:record_types,key',
            'deleted' => 'nullable|boolean',
            'record_category_id' => 'nullable|numeric|exists:record_categories:id',
        ];
    }
}
