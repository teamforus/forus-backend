<?php

namespace App\Http\Requests\Api\Records;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

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
            'type' => [
                'nullable',
                Rule::exists('record_types', 'key'),
            ],
            'record_category_id' => [
                'nullable',
                Rule::exists('record_categories', 'id'),
            ],
            'deleted' => [
                'nullable',
                'boolean',
            ],
        ];
    }
}
