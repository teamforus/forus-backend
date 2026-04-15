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
        $fieldRules = [$field->required && $this->isFieldFillableBySide($field, $byRequester) ? 'required' : 'nullable'];

        $fieldRules = [
            ...$fieldRules,
            ...match ($field->type) {
                ReservationField::TYPE_TEXT => ['string', 'max:200'],
                ReservationField::TYPE_NUMBER => ['int'],
                ReservationField::TYPE_BOOLEAN => ['string', Rule::in(ReservationField::BOOLEAN_VALUES)],
                ReservationField::TYPE_FILE => ['array', 'max:5'],
                default => ['in:'],
            },
            ...[$byRequester && !$field->isFillableByRequester() ? 'in:' : null],
            ...[!$byRequester && !$field->isFillableByProvider() ? 'in:' : null],
        ];

        return array_filter($fieldRules);
    }

    /**
     * @param ReservationField $field
     * @param bool $byRequester
     * @return bool
     */
    protected function isFieldFillableBySide(ReservationField $field, bool $byRequester): bool
    {
        return $byRequester ? $field->isFillableByRequester() : $field->isFillableByProvider();
    }

    /**
     * @return array
     */
    protected function getFileRule(): array
    {
        return [
            'required',
            'string',
            'max:255',
            Rule::exists('files', 'uid')
                ->whereNull('fileable_id')
                ->whereNull('fileable_type')
                ->where('type', 'product_reservation_custom_field')
                ->where('identity_address', $this->auth_address()),
        ];
    }
}
