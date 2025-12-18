<?php

namespace App\Http\Requests\Api\Platform\ProductReservations;

use App\Http\Requests\BaseFormRequest;
use App\Models\ReservationField;
use Illuminate\Validation\Rule;

class BaseProductReservationFieldRequest extends BaseFormRequest
{
    /**
     * @param ReservationField $field
     * @param bool $byRequester
     * @return array
     */
    protected function getCustomFieldRules(ReservationField $field, bool $byRequester): array
    {
        $fieldRules = [$field->isFillableByRequester() && $field->required ? 'required' : 'nullable'];

        $fieldRules = [
            ...$fieldRules,
            ...match ($field->type) {
                ReservationField::TYPE_TEXT => ['string', 'max:200'],
                ReservationField::TYPE_NUMBER => ['int'],
                ReservationField::TYPE_FILE => [
                    'string',
                    'max:255',
                    Rule::exists('files', 'uid')
                        ->whereNull('fileable_id')
                        ->whereNull('fileable_type')
                        ->where('type', 'product_reservation_custom_field')
                        ->where('identity_address', $this->auth_address()),
                ],
                default => ['string'],
            },
            ...[$byRequester && !$field->isFillableByRequester() ? 'in:' : null],
            ...[!$byRequester && !$field->isFillableByProvider() ? 'in:' : null],
        ];

        return array_filter($fieldRules);
    }
}
