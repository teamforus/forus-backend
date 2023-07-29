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
        return array_merge(
            $this->reservationRules(),
            $this->reservationCustomFieldRules(),
        );
    }

    /**
     * @return array
     */
    private function reservationCustomFieldRules(): array
    {
        if (!$this->organization->allow_reservation_custom_fields) {
            return [];
        }

        return [
            'fields' => 'nullable|array',
            'fields.*' => 'required|array',
            'fields.*.type' => [
                'required',
                Rule::in(OrganizationReservationField::$types),
            ],
            'fields.*.label' => 'required|string|max:200',
            'fields.*.description' => 'nullable|string|max:1000',
            'fields.*.required' => 'nullable|boolean',
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
