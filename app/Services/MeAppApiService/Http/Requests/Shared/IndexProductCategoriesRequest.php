<?php

namespace App\Services\MeAppApiService\Http\Requests\Shared;

use App\Http\Requests\BaseFormRequest;

class IndexProductCategoriesRequest extends BaseFormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'parent_id' => 'nullable|integer|exists:product_categories,id',
            'used' => 'boolean',
            'page' => $this->pageRule(),
            'per_page' => $this->perPageRule(1000),
        ];
    }
}
