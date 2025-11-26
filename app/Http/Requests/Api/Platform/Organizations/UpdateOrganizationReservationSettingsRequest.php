<?php

namespace App\Http\Requests\Api\Platform\Organizations;

use App\Models\Organization;
use App\Models\ReservationField;
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
    public function attributes(): array
    {
        return [
            'fields.*.type' => trans('validation.attributes.type'),
            'fields.*.label' => trans('validation.attributes.label'),
            'fields.*.description' => trans('validation.attributes.description'),
            'reservation_note' => trans('validation.attributes.reservation_note'),
            'reservation_note_text' => trans('validation.attributes.reservation_note_text'),
        ];
    }

    /**
     * @return array
     */
    public function messages(): array
    {
        return [
            'reservation_note_text.required_if_accepted' => trans('validation.required', [
                'attribute' => trans('validation.attributes.reservation_note_text'),
            ]),
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
            'fields.*.id' => [
                'nullable',
                'integer',
                Rule::exists('reservation_fields', 'id')
                    ->where('organization_id', $this->organization->id)
                    ->whereNull('product_id'),
            ],
            'fields.*.type' => [
                'required',
                Rule::in(ReservationField::TYPES),
            ],
            'fields.*.label' => 'required|string|max:200',
            'fields.*.required' => 'nullable|boolean',
            'fields.*.description' => 'nullable|string|max:1000',
        ];
    }
}
