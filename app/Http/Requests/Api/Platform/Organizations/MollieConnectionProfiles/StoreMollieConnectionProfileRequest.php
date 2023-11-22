<?php

namespace App\Http\Requests\Api\Platform\Organizations\MollieConnectionProfiles;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;

/**
 * @property-read Organization $organization
 */
class StoreMollieConnectionProfileRequest extends BaseFormRequest
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
            'name' => 'required|string|max:191',
            'email' => 'required|email|max:191',
            'website' => 'required|url|max:191',
            'phone' => 'required|string|max:191',
        ];
    }
}
