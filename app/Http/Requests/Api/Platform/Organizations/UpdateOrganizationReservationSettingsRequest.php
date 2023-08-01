<?php

namespace App\Http\Requests\Api\Platform\Organizations;

use App\Models\Organization;
use App\Models\OrganizationReservationField;
use Illuminate\Validation\Rule;

/**
 * @property-read Organization $organization
 */
class UpdateOrganizationReservationSettingsRequest extends BaseOrganizationRequest
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
            ...$this->reservationRules(),
            ...$this->reservationCustomFieldRules(),
        ];
    }

    /**
     * @return array
     */
    private function reservationCustomFieldRules(): array
    {
        return [
            'fields' => 'nullable|array|max:10',
            'fields.*' => 'required|array',
            'fields.*.type' => [
                'required',
                Rule::in(OrganizationReservationField::TYPES),
            ],
            'fields.*.label' => 'required|string|max:200',
            'fields.*.required' => 'nullable|boolean',
            'fields.*.description' => 'nullable|string|max:1000',
        ];
    }

    /**
     * @return array
     */
    public function attributes(): array
    {
        return [
            'fields.*.type' => trans('validation.attributes.type'),
            'fields.*.label' => trans('validation.attributes.label'),
            'fields.*.description' => trans('validation.attributes.description'),
        ];
    }
}
