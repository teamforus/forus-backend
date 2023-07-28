<?php

namespace App\Http\Requests\Api\Platform\Organizations\Reimbursements\Categories;

use App\Http\Requests\BaseFormRequest;

class StoreReimbursementCategoryRequest extends BaseFormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:200',
        ];
    }
}
