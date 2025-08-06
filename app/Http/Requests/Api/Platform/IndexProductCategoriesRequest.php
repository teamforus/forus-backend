<?php

namespace App\Http\Requests\Api\Platform;

use App\Http\Requests\BaseFormRequest;

class IndexProductCategoriesRequest extends BaseFormRequest
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
            'used' => 'boolean',
            'parent_id' => $this->parentIdRule(),
            'per_page' => $this->perPageRule(1000),
        ];
    }

    /**
     * @return string[]
     */
    protected function parentIdRule(): array
    {
        return [
            'nullable',
            $this->input('parent_id') === 'null' ? '' : 'exists:product_categories,id',
        ];
    }
}
