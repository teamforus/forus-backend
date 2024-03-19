<?php

namespace App\Http\Requests\Api\Platform\Organizations\Reimbursements\Categories;

use App\Http\Requests\BaseFormRequest;

class StoreReimbursementCategoryRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{name: 'required|string|max:200'}
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:200',
        ];
    }
}
