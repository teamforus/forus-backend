<?php

namespace App\Http\Requests\Api\Platform\Organizations\Reimbursements;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReimbursementRequest extends FormRequest
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
            'provider_name' => 'nullable|string|max:100',
            'category_name' => 'nullable|string|max:100',
        ];
    }
}
