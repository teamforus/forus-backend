<?php

namespace App\Http\Requests\Api\Platform\Organizations\ProductReservations;

use App\Http\Requests\Api\Platform\ProductReservations\BaseProductReservationFieldRequest;
use App\Models\Organization;
use App\Models\ProductReservation;
use App\Models\ReservationField;
use App\Rules\FileUidRule;
use Illuminate\Support\Facades\Gate;

/**
 * @property Organization $organization
 * @property ProductReservation $product_reservation
 * @property ReservationField $field
 */
class UpdateProductReservationFieldRequest extends BaseProductReservationFieldRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('updateCustomField', [$this->product_reservation, $this->organization, $this->field]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $value = $this->product_reservation->custom_fields()->where('reservation_field_id', $this->field->id)->first();

        return [
            'value' => $this->getCustomFieldRules($this->field, false),
            ...$this->field->type === ReservationField::TYPE_FILE ? [
                'value.*' => $value ? [
                    'required',
                    'string',
                    new FileUidRule('product_reservation_custom_field', $value),
                ] : $this->getFileRule(),
            ] : [],
        ];
    }
}
