<?php

namespace App\Http\Requests\Api\Platform\Organizations\Reimbursements;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property-read Organization $organization
 */
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
            'provider_name' => 'nullable|string|max:200',
            'reimbursement_category_id' => [
                'nullable',
                Rule::exists('reimbursement_categories', 'id')
                    ->where('organization_id', $this->organization->id)
            ],
        ];
    }
}
