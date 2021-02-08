<?php

namespace App\Http\Requests\Api\RecordCategories;

use App\Http\Requests\BaseFormRequest;

/**
 * Class RecordCategoryUpdateRequest
 * @package App\Http\Requests\Api\RecordCategories
 */
class RecordCategoryUpdateRequest extends BaseFormRequest
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
            'name'  => 'required|between:2,16',
            'order' => 'nullable|numeric|min:0',
        ];
    }
}
