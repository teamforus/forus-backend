<?php

namespace App\Http\Requests\Api\Platform\Organizations\ProductReservations;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\Organization;
use App\Models\Voucher;
use App\Rules\ProductReservations\ProductIdToReservationRule;
use App\Scopes\Builders\OrganizationQuery;
use Illuminate\Support\Facades\Gate;

/**
 * Class AcceptProductReservationRequest
 * @property Organization $organization
 * @package App\Http\Requests\Api\Platform\Organizations\ProductReservations
 */
class StoreProductReservationRequest extends BaseFormRequest
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
        $voucher = Voucher::findByAddressOrPhysicalCard($this->input('number'));

        $sponsorIsValid = OrganizationQuery::whereHasPermissionToScanVoucher(
            Organization::query(),
            $this->auth_address(),
            $voucher
        )->where('organizations.id', $this->organization->id)->exists();

        $addressIsValid = Gate::allows('useAsProvider', $voucher);

        return [
            'number' => [
                'required',
                'exists:physical_cards,code',
                $sponsorIsValid && $addressIsValid ? null : 'in:'
            ],
            'product_id' => [
                'required',
                'exists:products,id',
                new ProductIdToReservationRule($this->input('number')),
            ],
            'note' => 'nullable|string|max:2000',
        ];
    }
}
