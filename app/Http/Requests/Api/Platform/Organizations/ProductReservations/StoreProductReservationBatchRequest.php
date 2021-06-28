<?php

namespace App\Http\Requests\Api\Platform\Organizations\ProductReservations;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\Voucher;
use App\Rules\ProductIdToReservationRule;
use App\Rules\ProviderProductReservationBatchItemRule;
use App\Scopes\Builders\OrganizationQuery;
use Illuminate\Support\Facades\Gate;

/**
 * Class AcceptProductReservationRequest
 * @property Organization $organization
 * @package App\Http\Requests\Api\Platform\Organizations\ProductReservations
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
        return $this->isAuthenticated() && $this->organization->identityCan('scan_vouchers');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'reservations' => 'required|array|min:1',
            'reservations.*' => [
                'required',
                'array',
                new ProviderProductReservationBatchItemRule($this->organization),
            ],
        ];
    }
}
