<?php

namespace App\Http\Requests\Api\Records;

use App\Http\Requests\BaseFormRequest;
use App\Rules\RecordCategoryIdRule;

class RecordUpdateRequest extends BaseFormRequest
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
            'order' => 'nullable|numeric|min:0',
            'record_category_id' => ['nullable', new RecordCategoryIdRule()],
        ];
    }
}
