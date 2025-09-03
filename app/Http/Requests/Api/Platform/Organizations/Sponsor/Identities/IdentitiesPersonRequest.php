<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Identities;

use App\Http\Requests\BaseFormRequest;

class IdentitiesPersonRequest extends BaseFormRequest
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
            'scope' => 'nullable|in:parents,children,partners',
            'scope_id' => 'required_with:scope|integer',
        ];
    }
}
