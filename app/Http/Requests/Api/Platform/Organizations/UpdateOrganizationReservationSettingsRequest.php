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
     * @return ((\Illuminate\Validation\Rules\In|string)[]|string)[]
     *
     * @psalm-return array{fields: 'nullable|array|max:10', 'fields.*': 'required|array', 'fields.*.type': list{'required', \Illuminate\Validation\Rules\In}, 'fields.*.label': 'required|string|max:200', 'fields.*.required': 'nullable|boolean', 'fields.*.description': 'nullable|string|max:1000'}
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
     * @return (\Illuminate\Contracts\Translation\Translator|array|null|string)[]
     *
     * @psalm-return array{'fields.*.type': \Illuminate\Contracts\Translation\Translator|array|null|string, 'fields.*.label': \Illuminate\Contracts\Translation\Translator|array|null|string, 'fields.*.description': \Illuminate\Contracts\Translation\Translator|array|null|string}
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
