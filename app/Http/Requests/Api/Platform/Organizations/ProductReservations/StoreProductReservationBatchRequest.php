<?php

namespace App\Http\Requests\Api\Platform\Organizations\ProductReservations;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Rules\ProductReservations\ProviderProductReservationBatchItemPermissionsRule;
use App\Rules\ProductReservations\ProviderProductReservationBatchItemStockRule;
use App\Rules\ProductReservations\ProviderProductReservationBatchRule;
use Illuminate\Support\Facades\Validator;

/**
 * @property Organization $organization
 */
class StoreProductReservationBatchRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return
            $this->isAuthenticated() &&
            $this->organization->identityCan($this->identity(), 'scan_vouchers');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @param array|null $reservations
     * @return array
     */
    public function rules(?array $reservations = null): array
    {
        $reservations = $reservations ?: $this->input('reservations');

        // make collection rule
        $reservationsRule = new ProviderProductReservationBatchRule();

        // load all models for reservations collection
        $data = $reservationsRule->inflateReservationsData($reservations);

        return [
            'reservations' => array_merge(explode('|', 'required|array|min:1'), [
                new ProviderProductReservationBatchRule()
            ]),
            'reservations.*' => array_merge(explode('|', 'bail|required|array'), [
                // validate access products and vouchers
                new ProviderProductReservationBatchItemPermissionsRule($this->organization, $data),
                // validate stock and limitations
                new ProviderProductReservationBatchItemStockRule($this->organization, $data),
            ]),
        ];
    }

    /**
     * @param array $reservations
     * @return \Illuminate\Contracts\Validation\Validator|\Illuminate\Validation\Validator
     */
    public function validateRows($reservations = [])
    {
        return Validator::make(compact('reservations'), $this->rules($reservations));
    }
}
