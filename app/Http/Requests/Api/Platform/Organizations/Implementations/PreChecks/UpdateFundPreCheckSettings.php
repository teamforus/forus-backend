<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations\PreChecks;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;

/**
 * @property-read Organization $organization
 */
class UpdateFundPreCheckSettings extends BaseFormRequest
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
            'pre_check_excluded' => 'required|boolean',
            'pre_check_note' => 'nullable|string|max:2000',
        ];
    }
}
