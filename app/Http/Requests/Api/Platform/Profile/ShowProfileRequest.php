<?php

namespace App\Http\Requests\Api\Platform\Profile;

use App\Http\Requests\BaseFormRequest;

class ShowProfileRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return
            !$this->implementation()->isGeneral() &&
            $this->implementation()?->organization?->allow_profiles;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [];
    }
}