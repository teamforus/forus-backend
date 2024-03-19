<?php

namespace App\Http\Requests\Api\Platform;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;

class IndexProductCategoriesRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return (string|string[])[]
     *
     * @psalm-return array{q: 'nullable|string', used: 'boolean', used_type: string, parent_id: array<string>, per_page: string}
     */
    public function rules(): array
    {
        return [
            'q' => 'nullable|string',
            'used' => 'boolean',
            'used_type' => 'nullable|in:' . implode(',', Fund::TYPES),
            'parent_id' => $this->parentIdRule(),
            'per_page' => $this->perPageRule(1000),
        ];
    }

    /**
     * @return string[]
     *
     * @psalm-return list{'nullable', ''|'exists:product_categories,id'}
     */
    protected function parentIdRule(): array
    {
        return [
            'nullable',
            $this->input('parent_id') === 'null' ? '' : 'exists:product_categories,id'
        ];
    }
}
