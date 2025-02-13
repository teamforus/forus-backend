<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Identities;

use App\Http\Requests\BaseFormRequest;
use App\Models\RecordType;
use Illuminate\Validation\Rule;

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
            'birth_date' => 'sometimes|nullable|date_format:Y-m-d|before_or_equal:today',
            'city' => 'sometimes|nullable|string|city_name',
            'street' => 'sometimes|nullable|string|street_name',
            'house_number' => 'sometimes|nullable|string|house_number',
            'house_number_addition' => 'sometimes|nullable|string|house_addition',
            'postal_code' => 'sometimes|nullable|string|postcode',
            'client_number' => 'sometimes|nullable|string|min:2|max:40',
            'municipality_name' => 'sometimes|nullable|string|min:2|max:40',
            'age' => 'sometimes|nullable|numeric|min:0|max:150',
            'gender' => $this->recordTypeOptionRule(RecordType::findByKey('gender')),
            'marital_status' => $this->recordTypeOptionRule(RecordType::findByKey('marital_status')),
            'neighborhood_name' => 'sometimes|nullable|string|min:2|max:40',
            'house_composition' => $this->recordTypeOptionRule(RecordType::findByKey('house_composition')),
            'living_arrangement' => $this->recordTypeOptionRule(RecordType::findByKey('living_arrangement')),
        ];
    }

    /**
     * @param RecordType $type
     * @return array
     */
    protected function recordTypeOptionRule(RecordType $type): array
    {
        return [
            'sometimes',
            'nullable',
            Rule::exists('record_type_options', 'value')->where('record_type_id', $type->id),
        ];
    }
}
