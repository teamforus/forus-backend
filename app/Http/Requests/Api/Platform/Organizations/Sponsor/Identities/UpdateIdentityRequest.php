<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Identities;

use App\Http\Requests\BaseFormRequest;

class UpdateIdentityRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'given_name' => 'sometimes|nullable|string|min:2|max:40',
            'family_name' => 'sometimes|nullable|string|min:2|max:40',
            'telephone' => 'sometimes|nullable|string|min:2|max:40',
            'mobile' => 'sometimes|nullable|string|min:2|max:40',
            'birth_date' => 'sometimes|nullable|date_format:Y-m-d',
            'city' => 'sometimes|nullable|string|city_name',
            'street' => 'sometimes|nullable|string|street_name',
            'house_number' => 'sometimes|nullable|string|house_number',
            'house_number_addition' => 'sometimes|nullable|string|house_addition',
            'postal_code' => 'sometimes|nullable|string|postcode',
        ];
    }
}